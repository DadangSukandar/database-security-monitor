<?php

namespace App\Console\Commands;

use App\Models\SecurityAlert;
use App\Models\VulnerabilityFinding;
use App\Services\SecurityAlertFingerprintService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('security:backfill-alert-correlation {--dry-run : Preview changes without writing} {--apply : Apply safe singleton backfills}')]
#[Description('Safely backfill correlation metadata for historical security alerts')]
class BackfillSecurityAlertCorrelation extends Command
{
    /** @var array<string, string> */
    private const SKIP_REASON_LABELS = [
        'missing_connection' => 'Missing connection',
        'missing_rule_identity' => 'Missing rule identity',
        'missing_database' => 'Missing database',
        'missing_principal' => 'Missing principal',
        'missing_host' => 'Missing host/client IP',
        'missing_source_reference' => 'Missing source reference',
        'source_finding_unavailable' => 'Source finding unavailable',
        'unsupported_alert_type' => 'Unsupported alert type',
        'scope_mismatch' => 'Scope mismatch',
        'other' => 'Other',
    ];

    public function handle(SecurityAlertFingerprintService $fingerprints): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $alerts = SecurityAlert::query()->whereNull('fingerprint')->orderBy('id')->get();
        $findings = VulnerabilityFinding::query()
            ->with('assessment')
            ->get()
            ->keyBy('id');

        $candidates = collect();
        $skipReasons = collect();

        foreach ($alerts as $alert) {
            $candidate = $this->candidateFor($alert, $findings, $fingerprints);

            if (is_string($candidate)) {
                $skipReasons->push($candidate);

                continue;
            }

            $candidates->push($candidate);
        }

        $candidateGroups = $candidates->groupBy('fingerprint');
        $existingGroups = SecurityAlert::query()
            ->whereNotNull('fingerprint')
            ->get()
            ->groupBy('fingerprint');

        $duplicateGroups = $candidateGroups->filter(
            fn (Collection $group, string $fingerprint): bool => $group->count() > 1
                || $existingGroups->has($fingerprint)
        );

        $eligible = $candidateGroups
            ->reject(fn (Collection $group, string $fingerprint): bool => $group->count() > 1
                || $existingGroups->has($fingerprint))
            ->flatten(1);

        $collisionAlerts = $duplicateGroups->sum(fn (Collection $group): int => $group->count());
        $duplicateAlertsInvolved = $duplicateGroups->sum(
            fn (Collection $group, string $fingerprint): int => $group->count()
                + $existingGroups->get($fingerprint, collect())->count()
        );
        $potentialRedundantAlerts = max(0, $duplicateAlertsInvolved - $duplicateGroups->count());

        $updated = 0;

        if ($apply) {
            foreach ($eligible as $candidate) {
                $updated += $this->applyCandidate($candidate) ? 1 : 0;
            }
        }

        $validationSkipped = $skipReasons->count();
        $ambiguous = $validationSkipped + $collisionAlerts;

        $this->info('Security alert correlation backfill');
        $this->line('Mode                       : '.($apply ? 'APPLY' : 'DRY RUN'));
        $this->line('Alerts checked             : '.SecurityAlert::query()->count());
        $this->line('Missing fingerprint        : '.$alerts->count());
        $this->line('Fingerprints generated     : '.$candidates->count());
        $this->line('Safe backfills             : '.$eligible->count());
        $this->line('Ambiguous/skipped          : '.$ambiguous);
        $this->line('Validation skipped         : '.$validationSkipped);
        $this->line('Duplicate groups           : '.$duplicateGroups->count());
        $this->line('Collision candidate alerts : '.$collisionAlerts);
        $this->line('Duplicate alerts involved  : '.$duplicateAlertsInvolved);
        $this->line('Potential redundant alerts : '.$potentialRedundantAlerts);
        $this->line('Updated alerts             : '.$updated);
        $this->line('Missing optional resource  : '.$alerts->filter(
            fn (SecurityAlert $alert): bool => blank($alert->table_name)
        )->count().' (informational; not a skip reason)');

        $this->newLine();
        $this->warn('Ambiguous/skipped breakdown:');
        $reasonCounts = $skipReasons->countBy();

        foreach (self::SKIP_REASON_LABELS as $reason => $label) {
            $this->line(sprintf('%-28s : %d', $label, $reasonCounts->get($reason, 0)));
        }

        $this->line(sprintf('%-28s : %d', 'Collision/uncertain', $collisionAlerts));
        $this->line(sprintf('%-28s : %d', 'Breakdown total', $ambiguous));

        if ($duplicateGroups->isNotEmpty()) {
            $this->newLine();
            $this->warn('Potential duplicate groups (read-only):');
            $this->displayDuplicateGroups($duplicateGroups, $existingGroups);
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('Dry run only. Re-run with --apply to write eligible safe backfills.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, VulnerabilityFinding>  $findings
     * @return array{alert: SecurityAlert, fingerprint: string, assessment_id: int, rule_code: string}|string
     */
    private function candidateFor(
        SecurityAlert $alert,
        Collection $findings,
        SecurityAlertFingerprintService $fingerprints
    ): array|string {
        if ($alert->alert_type !== 'VULNERABILITY') {
            return 'unsupported_alert_type';
        }

        if (! preg_match('/^\[Assessment #(\d+)\] \[Finding #(\d+)\]/', (string) $alert->description, $matches)) {
            return 'missing_source_reference';
        }

        $assessmentId = (int) $matches[1];
        $finding = $findings->get((int) $matches[2]);

        if (
            $finding === null
            || $finding->assessment === null
            || (int) $finding->vulnerability_assessment_id !== $assessmentId
        ) {
            return 'source_finding_unavailable';
        }

        if (blank($finding->rule_code)) {
            return 'missing_rule_identity';
        }

        $assessment = $finding->assessment;
        if ($alert->database_connection_id === null || $assessment->database_connection_id === null) {
            return 'missing_connection';
        }

        $connectionId = (int) $assessment->database_connection_id;
        $databaseName = $finding->database_name
            ?: $assessment->database_name
            ?: null;

        if (blank($alert->database_name) || blank($databaseName)) {
            return 'missing_database';
        }

        if (blank($alert->username) || blank($finding->username)) {
            return 'missing_principal';
        }

        if (blank($alert->client_ip) || blank($finding->host)) {
            return 'missing_host';
        }

        $tableName = $finding->getAttribute('table_name') ?: $alert->table_name;

        if (
            (int) $alert->database_connection_id !== $connectionId
            || $fingerprints->normalize($alert->database_name) !== $fingerprints->normalize($databaseName)
            || $fingerprints->normalize($alert->username) !== $fingerprints->normalize($finding->username)
            || $fingerprints->normalize($alert->client_ip) !== $fingerprints->normalize($finding->host)
            || $fingerprints->normalize($alert->table_name) !== $fingerprints->normalize($tableName)
        ) {
            return 'scope_mismatch';
        }

        return [
            'alert' => $alert,
            'fingerprint' => $fingerprints->forVulnerabilityFinding(
                $connectionId,
                $databaseName,
                $finding,
                $tableName
            ),
            'assessment_id' => $assessmentId,
            'rule_code' => (string) $finding->rule_code,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, array{alert: SecurityAlert, fingerprint: string, assessment_id: int, rule_code: string}>>  $duplicateGroups
     * @param  Collection<string, Collection<int, SecurityAlert>>  $existingGroups
     */
    private function displayDuplicateGroups(Collection $duplicateGroups, Collection $existingGroups): void
    {
        foreach ($duplicateGroups->sortKeys() as $fingerprint => $candidates) {
            $alerts = $candidates->pluck('alert')
                ->concat($existingGroups->get($fingerprint, collect()))
                ->sortBy('id')
                ->values();
            $firstCandidate = $candidates->first();
            $detectedTimes = $alerts->pluck('detected_at')->filter()->map(
                fn ($timestamp): Carbon => Carbon::parse($timestamp)
            );
            $firstSeenTimes = $alerts->map(
                fn (SecurityAlert $alert) => $alert->first_seen_at ?? $alert->detected_at
            )->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp));
            $lastSeenTimes = $alerts->map(
                fn (SecurityAlert $alert) => $alert->last_seen_at ?? $alert->detected_at
            )->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp));
            $statuses = $alerts->countBy(fn (SecurityAlert $alert): string => (string) $alert->status)
                ->map(fn (int $count, string $status): string => "{$status}={$count}")
                ->values()
                ->implode(', ');

            $this->line('---');
            $this->line('Fingerprint prefix : '.substr((string) $fingerprint, 0, 12));
            $this->line('Alert IDs          : '.$alerts->pluck('id')->implode(', '));
            $this->line('Count              : '.$alerts->count());
            $this->line('Alert type         : '.($alerts->first()?->alert_type ?? '-'));
            $this->line('Rule / title       : '.$firstCandidate['rule_code'].' / '.($alerts->first()?->title ?? '-'));
            $this->line('Database           : '.($alerts->first()?->database_name ?? '-'));
            $this->line('Connection ID      : '.($alerts->first()?->database_connection_id ?? '-'));
            $this->line('Username           : '.($alerts->first()?->username ?? '-'));
            $this->line('Statuses           : '.($statuses !== '' ? $statuses : '-'));
            $this->line('Detected range     : '.$this->formatRange($detectedTimes));
            $this->line('First seen range   : '.$this->formatRange($firstSeenTimes));
            $this->line('Last seen range    : '.$this->formatRange($lastSeenTimes));
        }
    }

    /** @param Collection<int, Carbon> $timestamps */
    private function formatRange(Collection $timestamps): string
    {
        if ($timestamps->isEmpty()) {
            return '-';
        }

        return $timestamps->min()->format('Y-m-d H:i:s')
            .' -> '.$timestamps->max()->format('Y-m-d H:i:s');
    }

    /**
     * @param  array{alert: SecurityAlert, fingerprint: string, assessment_id: int, rule_code: string}  $candidate
     */
    private function applyCandidate(array $candidate): bool
    {
        return DB::transaction(function () use ($candidate): bool {
            $alert = SecurityAlert::query()
                ->whereKey($candidate['alert']->id)
                ->lockForUpdate()
                ->first();

            if (
                $alert === null
                || $alert->fingerprint !== null
                || SecurityAlert::query()->where('fingerprint', $candidate['fingerprint'])->exists()
            ) {
                return false;
            }

            $detectedAt = $alert->detected_at ?? $alert->created_at;
            $firstSeenAt = collect([
                $alert->first_seen_at,
                $detectedAt,
            ])->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp))->sort()->first() ?? now();
            $lastSeenAt = collect([
                $alert->last_seen_at,
                $detectedAt,
            ])->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp))->sortDesc()->first() ?? $firstSeenAt;

            $alert->update([
                'fingerprint' => $candidate['fingerprint'],
                'rule' => $candidate['rule_code'],
                'occurrence_count' => max(1, (int) $alert->occurrence_count),
                'first_seen_at' => $firstSeenAt,
                'last_seen_at' => $lastSeenAt,
                'last_assessment_id' => $candidate['assessment_id'],
            ]);

            return true;
        });
    }
}
