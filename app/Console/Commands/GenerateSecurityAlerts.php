<?php

namespace App\Console\Commands;

use App\Models\SecurityAlertHistory;
use App\Models\SecurityAlert;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
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
    public function handle(): int
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

        if (!$assessment) {

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
            'Assessment ID : ' .
            $assessment->id
        );

        $this->line(
            'Database      : ' .
            (
                $assessment->database_name
                ?? '-'
            )
        );

        $this->line(
            'Score         : ' .
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


                /*
                 * -------------------------------------------------
                 * DESCRIPTION
                 *
                 * Assessment ID + Finding ID digunakan sebagai
                 * identitas unik karena tabel security_alerts lama
                 * belum mempunyai vulnerability_finding_id.
                 * -------------------------------------------------
                 */

                $description =
                    '[Assessment #' .
                    $assessment->id .
                    '] ' .
                    '[Finding #' .
                    $finding->id .
                    '] ';


                if ($finding->description) {

                    $description .=
                        $finding->description;

                } else {

                    $description .=
                        'Security vulnerability detected.';
                }


                /*
                 * -------------------------------------------------
                 * CHECK DUPLICATE
                 * -------------------------------------------------
                 */

                $existingAlert =
                    SecurityAlert::query()
                        ->where(
                            'alert_type',
                            $alertType
                        )
                        ->where(
                            'database_name',
                            $databaseName
                        )
                        ->where(
                            'title',
                            $finding->title
                        )
                        ->where(
                            'description',
                            'like',
                            '[Assessment #' .
                            $assessment->id .
                            '] [Finding #' .
                            $finding->id .
                            ']%'
                        )
                        ->first();


                if ($existingAlert) {

                    $existing++;

                    $this->line(
                        '[EXISTS] [' .
                        strtoupper(
                            $finding->severity
                        ) .
                        '] ' .
                        $finding->title
                    );

                    continue;
                }


                /*
                 * -------------------------------------------------
                 * CREATE ALERT
                 * -------------------------------------------------
                 */

                SecurityAlert::create([

                    /*
                     * Existing activity relation tidak digunakan
                     * untuk vulnerability assessment alert.
                     */
                    'database_activity_id' =>
                        null,

                    /*
                     * Jika assessment mempunyai connection ID,
                     * gunakan nilainya.
                     */
                    'database_connection_id' =>
                        $this->getConnectionId(
                            $assessment
                        ),

                    'database_name' =>
                        $databaseName,

                    'username' =>
                        $finding->username
                        ?: null,

                    'client_ip' =>
                        $finding->host
                        ?: null,

                    'alert_type' =>
                        $alertType,

                    'severity' =>
                        strtoupper(
                            $finding->severity
                        ),

                    'title' =>
                        $finding->title,

                    'description' =>
                        $description,

                    /*
                     * Evidence kita simpan di query apabila
                     * tersedia karena schema lama menyediakan
                     * kolom query.
                     */
                    'query' =>
                        $finding->evidence
                        ?: null,

                    /*
                     * Vulnerability finding belum tentu
                     * mempunyai table_name.
                     */
                    'table_name' =>
                        $finding->table_name
                        ?? null,

                    'status' =>
                        'OPEN',

                    'detected_at' =>
                        now(),

                    'resolved_at' =>
                        null,

                    'resolution_note' =>
                        null,
                ]);


                $created++;


                /*
                 * -------------------------------------------------
                 * OUTPUT
                 * -------------------------------------------------
                 */

                $this->info(
                    '[NEW] [' .
                    strtoupper(
                        $finding->severity
                    ) .
                    '] ' .
                    $finding->title
                );

            } catch (Throwable $e) {

                $failed++;

                $this->error(
                    '[FAILED] Finding #' .
                    $finding->id .
                    ' - ' .
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
            'Assessment : #' .
            $assessment->id
        );

        $this->line(
            'Critical   : ' .
            $findings
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->count()
        );

        $this->line(
            'High       : ' .
            $findings
                ->where(
                    'severity',
                    'HIGH'
                )
                ->count()
        );

        $this->line(
            'Created    : ' .
            $created
        );

        $this->line(
            'Existing   : ' .
            $existing
        );

        $this->line(
            'Failed     : ' .
            $failed
        );

        $this->line(
            'Total      : ' .
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
}