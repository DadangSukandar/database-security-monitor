<?php

namespace App\Console\Commands;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use App\Services\SecurityAlertFingerprintService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateSecurityAlerts extends Command
{
    /**
     * =========================================================
     * COMMAND
     * =========================================================
     *
     * php artisan security:generate-alerts
     *
     * Assessment tertentu:
     *
     * php artisan security:generate-alerts --assessment=24
     */
    protected $signature = 'security:generate-alerts
                            {--assessment= : Assessment ID tertentu}';

    protected $description =
        'Generate security alerts dari HIGH dan CRITICAL vulnerability findings';

    /**
     * =========================================================
     * HANDLE
     * =========================================================
     */
    public function handle(SecurityAlertFingerprintService $fingerprints): int
    {
        $this->newLine();

        $this->line(
            '=============================================='
        );

        $this->info(
            '       GENERATE SECURITY ALERTS'
        );

        $this->line(
            '=============================================='
        );

        $this->newLine();

        /*
         * =====================================================
         * GET ASSESSMENT
         * =====================================================
         */

        if ($this->option('assessment')) {

            $assessment =
                VulnerabilityAssessment::find(
                    $this->option('assessment')
                );

        } else {

            $assessment =
                VulnerabilityAssessment::query()
                    ->latest('id')
                    ->first();
        }

        /*
         * =====================================================
         * ASSESSMENT NOT FOUND
         * =====================================================
         */

        if (! $assessment) {

            $this->warn(
                'Assessment tidak ditemukan.'
            );

            return self::SUCCESS;
        }

        /*
         * =====================================================
         * DISPLAY ASSESSMENT
         * =====================================================
         */

        $this->line(
            'Assessment ID : '.
            $assessment->id
        );

        $this->line(
            'Database      : '.
            (
                $assessment->database_name
                ?? '-'
            )
        );

        $this->line(
            'Score         : '.
            (
                $assessment->score
                ?? '-'
            )
        );

        $this->newLine();

        /*
         * =====================================================
         * GET HIGH / CRITICAL FINDINGS
         * =====================================================
         */

        $findings =
            VulnerabilityFinding::query()
                ->where(
                    'vulnerability_assessment_id',
                    $assessment->id
                )
                ->whereIn(
                    'severity',
                    [
                        'HIGH',
                        'CRITICAL',
                    ]
                )
                ->orderByRaw(
                    "
                    CASE UPPER(severity)
                        WHEN 'CRITICAL' THEN 1
                        WHEN 'HIGH' THEN 2
                        ELSE 3
                    END
                    "
                )
                ->orderBy('id')
                ->get();

        /*
         * =====================================================
         * NO HIGH / CRITICAL
         * =====================================================
         */

        if ($findings->isEmpty()) {

            $this->info(
                'Tidak ada HIGH atau CRITICAL finding.'
            );

            return self::SUCCESS;
        }

        /*
         * =====================================================
         * COUNTERS
         * =====================================================
         */

        $created = 0;

        $correlated = 0;

        $reopened = 0;

        $existing = 0;

        $failed = 0;

        /*
         * =====================================================
         * PROCESS FINDINGS
         * =====================================================
         */

        foreach ($findings as $finding) {

            try {

                /*
                 * -------------------------------------------------
                 * ALERT TYPE
                 * -------------------------------------------------
                 */

                $alertType =
                    'VULNERABILITY';

                /*
                 * -------------------------------------------------
                 * DATABASE NAME
                 * -------------------------------------------------
                 */

                $databaseName =
                    $finding->database_name
                    ?: $assessment->database_name
                    ?: 'Unknown Database';

                $description =
                    '[Assessment #'.
                    $assessment->id.
                    '] '.
                    '[Finding #'.
                    $finding->id.
                    '] ';

                if ($finding->description) {

                    $description .=
                        $finding->description;

                } else {

                    $description .=
                        'Security vulnerability detected.';
                }

                $connectionId = $this->getConnectionId($assessment);
                $tableName = $finding->getAttribute('table_name') ?: null;
                $fingerprint = $fingerprints->forVulnerabilityFinding(
                    $connectionId,
                    $databaseName,
                    $finding,
                    $tableName
                );
                $seenAt = $assessment->scanned_at ?? $finding->created_at ?? now();

                $outcome = DB::transaction(function () use (
                    $alertType,
                    $assessment,
                    $connectionId,
                    $databaseName,
                    $description,
                    $finding,
                    $fingerprint,
                    $seenAt,
                    $tableName
                ): string {
                    $alert = SecurityAlert::query()
                        ->canonical()
                        ->where('fingerprint', $fingerprint)
                        ->lockForUpdate()
                        ->first();

                    if ($alert === null) {
                        $alert = $this->findLegacyAlert(
                            $alertType,
                            $connectionId,
                            $databaseName,
                            $finding,
                            $tableName
                        );
                    }

                    if ($alert === null) {
                        SecurityAlert::query()->create([
                            'database_activity_id' => null,
                            'database_connection_id' => $connectionId,
                            'database_name' => $databaseName,
                            'username' => $finding->username ?: null,
                            'client_ip' => $finding->host ?: null,
                            'alert_type' => $alertType,
                            'fingerprint' => $fingerprint,
                            'rule' => $finding->rule_code ?: null,
                            'severity' => strtoupper((string) $finding->severity),
                            'title' => $finding->title,
                            'description' => $description,
                            'query' => $finding->evidence ?: null,
                            'table_name' => $tableName,
                            'status' => 'OPEN',
                            'occurrence_count' => 1,
                            'first_seen_at' => $seenAt,
                            'last_seen_at' => $seenAt,
                            'last_assessment_id' => $assessment->id,
                            'detected_at' => $seenAt,
                            'resolved_at' => null,
                            'resolution_note' => null,
                        ]);

                        return 'created';
                    }

                    if (
                        (int) $alert->last_assessment_id === (int) $assessment->id
                        || $this->isLegacyOccurrenceFromAssessment($alert, $assessment)
                    ) {
                        $alert->update([
                            'fingerprint' => $fingerprint,
                            'rule' => $finding->rule_code ?: $alert->rule,
                            'occurrence_count' => max(1, (int) $alert->occurrence_count),
                            'first_seen_at' => $alert->first_seen_at ?? $alert->detected_at ?? $seenAt,
                            'last_seen_at' => $alert->last_seen_at ?? $seenAt,
                            'last_assessment_id' => $assessment->id,
                        ]);

                        return 'existing';
                    }

                    $oldStatus = strtoupper((string) $alert->status);
                    $reopened = $oldStatus === 'RESOLVED';

                    $alert->update([
                        'fingerprint' => $fingerprint,
                        'rule' => $finding->rule_code ?: $alert->rule,
                        'severity' => strtoupper((string) $finding->severity),
                        'title' => $finding->title,
                        'description' => $description,
                        'query' => $finding->evidence ?: null,
                        'occurrence_count' => max(1, (int) $alert->occurrence_count) + 1,
                        'first_seen_at' => $alert->first_seen_at ?? $alert->detected_at ?? $seenAt,
                        'last_seen_at' => $seenAt,
                        'last_assessment_id' => $assessment->id,
                        'status' => $reopened ? 'OPEN' : $alert->status,
                        'acknowledged_at' => $reopened ? null : $alert->acknowledged_at,
                        'resolved_at' => $reopened ? null : $alert->resolved_at,
                        'resolution_note' => $reopened ? null : $alert->resolution_note,
                    ]);

                    if ($reopened) {
                        SecurityAlertHistory::query()->create([
                            'security_alert_id' => $alert->id,
                            'action' => 'AUTO_REOPEN',
                            'old_status' => $oldStatus,
                            'new_status' => 'OPEN',
                            'notes' => 'Finding ditemukan kembali pada assessment #'.$assessment->id.'.',
                        ]);

                        return 'reopened';
                    }

                    return 'correlated';
                });

                match ($outcome) {
                    'created' => $created++,
                    'correlated' => $correlated++,
                    'reopened' => $reopened++,
                    default => $existing++,
                };

                $label = match ($outcome) {
                    'created' => 'NEW',
                    'correlated' => 'CORRELATED',
                    'reopened' => 'REOPENED',
                    default => 'EXISTS',
                };

                $this->{$outcome === 'created' ? 'info' : 'line'}(
                    '['.$label.'] ['.strtoupper((string) $finding->severity).'] '.$finding->title
                );

            } catch (Throwable $e) {

                $failed++;

                $this->error(
                    '[FAILED] Finding #'.
                    $finding->id.
                    ' - '.
                    $e->getMessage()
                );
            }
        }

        /*
         * =====================================================
         * SUMMARY
         * =====================================================
         */

        $this->newLine();

        $this->line(
            '=============================================='
        );

        $this->info(
            'ALERT GENERATION SELESAI'
        );

        $this->line(
            '=============================================='
        );

        $this->line(
            'Assessment : #'.
            $assessment->id
        );

        $this->line(
            'Critical   : '.
            $findings
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->count()
        );

        $this->line(
            'High       : '.
            $findings
                ->where(
                    'severity',
                    'HIGH'
                )
                ->count()
        );

        $this->line(
            'Created    : '.
            $created
        );

        $this->line(
            'Existing   : '.
            $existing
        );

        $this->line(
            'Correlated : '.
            $correlated
        );

        $this->line(
            'Reopened   : '.
            $reopened
        );

        $this->line(
            'Failed     : '.
            $failed
        );

        $this->line(
            'Total      : '.
            $findings->count()
        );

        $this->newLine();

        /*
         * =====================================================
         * RETURN
         * =====================================================
         */

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * =========================================================
     * GET DATABASE CONNECTION ID
     * =========================================================
     */
    private function getConnectionId(
        VulnerabilityAssessment $assessment
    ): ?int {

        /*
         * Beberapa versi schema project mungkin menggunakan
         * nama kolom yang berbeda.
         */

        $possibleColumns = [

            'database_connection_id',

            'connection_id',

            'security_connection_id',
        ];

        foreach ($possibleColumns as $column) {

            $value =
                $assessment->getAttribute(
                    $column
                );

            if ($value !== null) {

                return (int) $value;
            }
        }

        return null;
    }

    private function findLegacyAlert(
        string $alertType,
        ?int $connectionId,
        string $databaseName,
        VulnerabilityFinding $finding,
        ?string $tableName
    ): ?SecurityAlert {
        return SecurityAlert::query()
            ->canonical()
            ->whereNull('fingerprint')
            ->where('alert_type', $alertType)
            ->when(
                $connectionId === null,
                fn ($query) => $query->whereNull('database_connection_id'),
                fn ($query) => $query->where('database_connection_id', $connectionId)
            )
            ->where('database_name', $databaseName)
            ->where('title', $finding->title)
            ->when(
                $finding->username,
                fn ($query) => $query->where('username', $finding->username),
                fn ($query) => $query->whereNull('username')
            )
            ->when(
                $finding->host,
                fn ($query) => $query->where('client_ip', $finding->host),
                fn ($query) => $query->whereNull('client_ip')
            )
            ->when(
                $tableName,
                fn ($query) => $query->where('table_name', $tableName),
                fn ($query) => $query->whereNull('table_name')
            )
            ->lockForUpdate()
            ->first();
    }

    private function isLegacyOccurrenceFromAssessment(
        SecurityAlert $alert,
        VulnerabilityAssessment $assessment
    ): bool {
        return $alert->last_assessment_id === null
            && str_starts_with(
                (string) $alert->description,
                '[Assessment #'.$assessment->id.'] '
            );
    }
}
