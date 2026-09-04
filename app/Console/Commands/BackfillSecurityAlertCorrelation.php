<?php

namespace App\Console\Commands;

use App\Console\Concerns\HandlesSafeConsoleExceptions;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\VulnerabilityFinding;
use App\Services\SecurityAlertFingerprintService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('security:backfill-alert-correlation {--dry-run : Preview changes without writing} {--apply : Apply safe singleton backfills} {--consolidate : Preview non-destructive historical consolidation}')]
#[Description('Safely backfill correlation metadata for historical security alerts')]
class BackfillSecurityAlertCorrelation extends Command
{
    use HandlesSafeConsoleExceptions;

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
        $alerts = SecurityAlert::query()->canonical()->whereNull('fingerprint')->orderBy('id')->get();
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
            ->canonical()
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

        if ($this->option('consolidate')) {
            if ($apply && ! $this->supportsConsolidation()) {
                $this->error('Consolidation schema support is not available. Run migrations first.');

                return self::FAILURE;
            }

            $plans = $this->consolidationPlans($duplicateGroups, $existingGroups);

            try {
                $updated = $apply ? $this->applyConsolidationPlans($plans) : 0;
            } catch (Throwable $exception) {
                $this->reportConsoleException($exception);

                $this->error(
                    'Historical consolidation failed and all changes were rolled back.'
                );

                $this->error(
                    $this->safeConsoleExceptionMessage()
                );

                return self::FAILURE;
            }

            $this->displayConsolidationPlan($plans, $apply, $updated);

            return self::SUCCESS;
        }

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

    /**
     * @param  Collection<string, Collection<int, array{alert: SecurityAlert, fingerprint: string, assessment_id: int, rule_code: string}>>  $duplicateGroups
     * @param  Collection<string, Collection<int, SecurityAlert>>  $existingGroups
     */
    private function consolidationPlans(Collection $duplicateGroups, Collection $existingGroups): Collection
    {
        return $duplicateGroups->map(
            fn (Collection $candidates, string $fingerprint): array => $this->consolidationPlanForGroup(
                $fingerprint,
                $candidates,
                $existingGroups->get($fingerprint, collect())
            )
        )->values();
    }

    /** @param Collection<int, array<string, mixed>> $plans */
    private function displayConsolidationPlan(Collection $plans, bool $apply, int $updated): void
    {
        $supportsConsolidation = $this->supportsConsolidation();

        $readyGroups = $supportsConsolidation ? $plans->where('safe', true)->count() : 0;
        $unsafeGroups = $plans->count() - $readyGroups;

        $this->info('Historical security alert consolidation plan');
        $this->line('Mode                           : '.($apply ? 'APPLY (CONSOLIDATION)' : 'DRY RUN (CONSOLIDATION)'));
        $this->line('Groups                         : '.$plans->count());
        $this->line('Canonical alerts               : '.$plans->count());
        $this->line('Historical duplicates          : '.$plans->sum('duplicate_count'));
        $this->line('Distinct occurrences           : '.$plans->sum('occurrence_count'));
        $this->line('Histories preserved            : '.$plans->sum('history_count'));
        $this->line('References requiring migration : '.($supportsConsolidation ? 0 : $plans->sum('duplicate_count')));
        $this->line('Unsafe groups                  : '.$unsafeGroups);
        $this->line('Ready groups                   : '.$readyGroups);
        $this->line('Rows changed                   : '.$updated);

        $this->newLine();
        $this->warn('Consolidation groups (read-only):');

        foreach ($plans as $plan) {
            $this->line('---');
            $this->line('Fingerprint prefix  : '.$plan['fingerprint_prefix']);
            $this->line('Canonical ID        : '.$plan['canonical_id']);
            $this->line('Duplicate IDs       : '.implode(', ', $plan['duplicate_ids']));
            $this->line('Duplicate count     : '.$plan['duplicate_count']);
            $this->line('Occurrence count    : '.$plan['occurrence_count']);
            $this->line('First seen          : '.$plan['first_seen']);
            $this->line('Last seen           : '.$plan['last_seen']);
            $this->line('Final status        : '.$plan['final_status']);
            $this->line('Highest severity    : '.$plan['highest_severity']);
            $this->line('Latest assessment   : '.($plan['latest_assessment_id'] ?? '-'));
            $this->line('Histories preserved : '.$plan['history_count']);
            $this->line('Acknowledged        : '.($plan['has_acknowledged'] ? 'YES' : 'NO'));
            $this->line('Resolved            : '.($plan['has_resolved'] ? 'YES' : 'NO'));
            $this->line('Resolution note     : '.($plan['has_resolution_note'] ? 'YES' : 'NO'));
            $this->line('External references : 0');
            $this->line('Safety result       : '.($supportsConsolidation && $plan['safe']
                ? 'READY'
                : 'MIGRATION REQUIRED'));
        }

        $this->newLine();
        $this->comment($apply
            ? 'Consolidation completed without deleting historical alerts.'
            : 'Dry run only. No alerts, histories, or references were changed.');
    }

    /**
     * @param  Collection<int, array{alert: SecurityAlert, fingerprint: string, assessment_id: int, rule_code: string}>  $candidates
     * @param  Collection<int, SecurityAlert>  $existingAlerts
     * @return array<string, mixed>
     */
    private function consolidationPlanForGroup(
        string $fingerprint,
        Collection $candidates,
        Collection $existingAlerts
    ): array {
        $alerts = $candidates->pluck('alert')
            ->concat($existingAlerts)
            ->unique('id')
            ->sortBy('id')
            ->values();
        $canonical = $alerts->sortBy(
            fn (SecurityAlert $alert): string => sprintf(
                '%s-%020d',
                $alert->detected_at?->format('Y-m-d H:i:s.u') ?? '9999-12-31 23:59:59.999999',
                $alert->id
            )
        )->first();
        $histories = SecurityAlertHistory::query()
            ->whereIn('security_alert_id', $alerts->pluck('id'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $occurrences = collect();

        foreach ($candidates as $candidate) {
            $occurrences->push([
                'assessment_id' => $candidate['assessment_id'],
                'timestamp' => $candidate['alert']->last_seen_at
                    ?? $candidate['alert']->detected_at
                    ?? $candidate['alert']->created_at,
            ]);
        }

        foreach ($existingAlerts as $alert) {
            $assessmentIds = collect([$alert->last_assessment_id]);

            if (preg_match('/^\[Assessment #(\d+)\]/', (string) $alert->description, $matches)) {
                $assessmentIds->push((int) $matches[1]);
            }

            foreach ($assessmentIds->filter()->unique() as $assessmentId) {
                $occurrences->push([
                    'assessment_id' => (int) $assessmentId,
                    'timestamp' => $alert->last_seen_at ?? $alert->detected_at ?? $alert->created_at,
                ]);
            }
        }

        $occurrences = $occurrences
            ->filter(fn (array $occurrence): bool => $occurrence['assessment_id'] !== null)
            ->sortBy('timestamp')
            ->unique('assessment_id')
            ->values();
        $latestOccurrence = $occurrences->sortByDesc('timestamp')->first();
        $firstSeen = $alerts->map(
            fn (SecurityAlert $alert) => $alert->first_seen_at ?? $alert->detected_at ?? $alert->created_at
        )->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp))->min();
        $lastSeen = $alerts->map(
            fn (SecurityAlert $alert) => $alert->last_seen_at ?? $alert->detected_at ?? $alert->created_at
        )->filter()->map(fn ($timestamp): Carbon => Carbon::parse($timestamp))->max();
        $severityRanks = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];
        $highestSeverity = $alerts->sortByDesc(
            fn (SecurityAlert $alert): int => $severityRanks[strtoupper((string) $alert->severity)] ?? 0
        )->first()?->severity ?? 'LOW';

        return [
            'fingerprint' => $fingerprint,
            'fingerprint_prefix' => substr($fingerprint, 0, 12),
            'canonical_id' => $canonical?->id,
            'alert_ids' => $alerts->pluck('id')->all(),
            'duplicate_ids' => $alerts->where('id', '!=', $canonical?->id)->pluck('id')->all(),
            'duplicate_count' => max(0, $alerts->count() - 1),
            'occurrence_count' => $occurrences->count(),
            'first_seen' => $firstSeen?->format('Y-m-d H:i:s') ?? '-',
            'last_seen' => $lastSeen?->format('Y-m-d H:i:s') ?? '-',
            'final_status' => $this->chronologicalStatus($alerts, $histories),
            'highest_severity' => strtoupper((string) $highestSeverity),
            'latest_assessment_id' => $latestOccurrence['assessment_id'] ?? null,
            'history_count' => $histories->count(),
            'has_acknowledged' => $alerts->contains(fn (SecurityAlert $alert): bool => $alert->acknowledged_at !== null)
                || $histories->contains(fn ($history): bool => $history->new_status === 'ACKNOWLEDGED'),
            'has_resolved' => $alerts->contains(fn (SecurityAlert $alert): bool => $alert->resolved_at !== null)
                || $histories->contains(fn ($history): bool => $history->new_status === 'RESOLVED'),
            'has_resolution_note' => $alerts->contains(fn (SecurityAlert $alert): bool => filled($alert->resolution_note)),
            'safe' => $canonical !== null && $occurrences->isNotEmpty(),
        ];
    }

    private function supportsConsolidation(): bool
    {
        return Schema::hasColumn('security_alerts', 'canonical_alert_id')
            && Schema::hasColumn('security_alerts', 'consolidated_at');
    }

    /** @param Collection<int, array<string, mixed>> $plans */
    private function applyConsolidationPlans(Collection $plans): int
    {
        return DB::transaction(function () use ($plans): int {
            $updated = 0;

            foreach ($plans->where('safe', true) as $plan) {
                $alerts = SecurityAlert::query()
                    ->whereIn('id', $plan['alert_ids'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $canonical = $alerts->get($plan['canonical_id']);
                $duplicates = $alerts->except([$plan['canonical_id']]);

                if (
                    $canonical === null
                    || $canonical->canonical_alert_id !== null
                    || $duplicates->isEmpty()
                    || $duplicates->contains(fn (SecurityAlert $alert): bool => $alert->id === $canonical->id)
                    || $duplicates->contains(fn (SecurityAlert $alert): bool => $alert->canonical_alert_id !== null)
                ) {
                    continue;
                }

                $oldStatus = (string) $canonical->status;
                $finalStatus = (string) $plan['final_status'];
                $latestAcknowledgedAt = $alerts->pluck('acknowledged_at')->filter()->max();
                $latestResolvedAlert = $alerts->filter(
                    fn (SecurityAlert $alert): bool => $alert->resolved_at !== null
                )->sortByDesc('resolved_at')->first();

                $canonical->update([
                    'fingerprint' => $plan['fingerprint'],
                    'occurrence_count' => $plan['occurrence_count'],
                    'first_seen_at' => $plan['first_seen'],
                    'last_seen_at' => $plan['last_seen'],
                    'last_assessment_id' => $plan['latest_assessment_id'],
                    'severity' => $plan['highest_severity'],
                    'status' => $finalStatus,
                    'acknowledged_at' => $finalStatus === 'OPEN' ? null : $latestAcknowledgedAt,
                    'resolved_at' => $finalStatus === 'RESOLVED' ? $latestResolvedAlert?->resolved_at : null,
                    'resolution_note' => $finalStatus === 'RESOLVED' ? $latestResolvedAlert?->resolution_note : null,
                ]);

                $consolidatedAt = now();

                foreach ($duplicates as $duplicate) {
                    $duplicate->update([
                        'canonical_alert_id' => $canonical->id,
                        'consolidated_at' => $consolidatedAt,
                    ]);
                }

                SecurityAlertHistory::query()->firstOrCreate(
                    [
                        'security_alert_id' => $canonical->id,
                        'action' => 'HISTORICAL_CONSOLIDATION',
                    ],
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $finalStatus,
                        'notes' => 'Consolidated '.$duplicates->count().' historical duplicate alerts without deleting evidence.',
                    ]
                );

                $updated += $duplicates->count() + 1;
            }

            return $updated;
        });
    }

    /**
     * @param  Collection<int, SecurityAlert>  $alerts
     * @param  Collection<int, SecurityAlertHistory>  $histories
     */
    private function chronologicalStatus(Collection $alerts, Collection $histories): string
    {
        $events = collect();

        foreach ($alerts as $alert) {
            $detectedAt = $alert->detected_at ?? $alert->created_at;

            if ($detectedAt !== null) {
                $events->push(['timestamp' => $detectedAt, 'status' => 'OPEN', 'priority' => 1]);
            }

            if (
                $alert->last_seen_at !== null
                && ($detectedAt === null || $alert->last_seen_at->gt($detectedAt))
            ) {
                $events->push(['timestamp' => $alert->last_seen_at, 'status' => 'OPEN', 'priority' => 1]);
            }

            if ($alert->acknowledged_at !== null) {
                $events->push(['timestamp' => $alert->acknowledged_at, 'status' => 'ACKNOWLEDGED', 'priority' => 2]);
            }

            if ($alert->resolved_at !== null) {
                $events->push(['timestamp' => $alert->resolved_at, 'status' => 'RESOLVED', 'priority' => 3]);
            }
        }

        foreach ($histories as $history) {
            $events->push([
                'timestamp' => $history->created_at,
                'status' => strtoupper((string) $history->new_status),
                'priority' => 4,
            ]);
        }

        return (string) ($events->sortBy(fn (array $event): string => sprintf(
            '%s-%d',
            Carbon::parse($event['timestamp'])->format('Y-m-d H:i:s.u'),
            $event['priority']
        ))->last()['status'] ?? 'OPEN');
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
