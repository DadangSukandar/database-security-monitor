<?php

namespace App\Console\Commands;

use App\Console\Concerns\HandlesSafeConsoleExceptions;
use App\Models\DatabaseConnection;
use App\Models\VulnerabilityAssessment;
use Illuminate\Console\Command;
use Throwable;

class SecurityAutoScan extends Command
{
    use HandlesSafeConsoleExceptions;

    /**
     * Command:
     *
     * php artisan security:auto-scan
     *
     * Atau database tertentu:
     *
     * php artisan security:auto-scan --connection=7
     */
    protected $signature = 'security:auto-scan
                            {--connection= : Scan hanya database connection tertentu}';

    protected $description =
        'Menjalankan vulnerability scan otomatis dan membuat security alert';

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
            '        AUTOMATIC SECURITY SCAN'
        );

        $this->line(
            '=============================================='
        );

        $this->newLine();

        /*
         * =====================================================
         * DATABASE CONNECTION QUERY
         * =====================================================
         */

        $query = DatabaseConnection::query();

        /*
         * Jika connection tertentu dipilih.
         */
        if ($this->option('connection')) {

            $query->where(
                'id',
                $this->option('connection')
            );

        } else {

            /*
             * Hanya database aktif.
             */
            $query->where(
                'is_active',
                true
            );
        }

        $connections = $query
            ->orderBy('id')
            ->get();

        /*
         * =====================================================
         * NO CONNECTION
         * =====================================================
         */

        if ($connections->isEmpty()) {

            $this->warn(
                'Tidak ada database connection aktif.'
            );

            return self::SUCCESS;
        }

        $this->line(
            'Database ditemukan : '.
            $connections->count()
        );

        $this->newLine();

        /*
         * =====================================================
         * COUNTERS
         * =====================================================
         */

        $scanSuccess = 0;

        $scanFailed = 0;

        $alertSuccess = 0;

        $alertFailed = 0;

        /*
         * =====================================================
         * LOOP CONNECTIONS
         * =====================================================
         */

        foreach ($connections as $connection) {

            $this->line(
                '----------------------------------------------'
            );

            $this->info(
                'Scanning: '.
                ($connection->name ?? 'Unnamed Database')
            );

            $this->line(
                'Connection ID : '.
                $connection->id
            );

            $this->line(
                'Driver        : '.
                strtoupper(
                    $connection->driver ?? '-'
                )
            );

            $this->newLine();

            try {

                /*
                 * =============================================
                 * SIMPAN ASSESSMENT TERAKHIR SEBELUM SCAN
                 * =============================================
                 */

                $beforeAssessmentId =
                    VulnerabilityAssessment::query()
                        ->where(
                            'database_connection_id',
                            $connection->id
                        )
                        ->max('id');

                /*
                 * =============================================
                 * RUN SECURITY SCAN
                 * =============================================
                 */

                $scanExitCode =
                    $this->call(
                        'security:scan-connection',
                        [
                            'connection' => $connection->id,
                        ]
                    );

                /*
                 * =============================================
                 * CHECK SCAN RESULT
                 * =============================================
                 */

                if ($scanExitCode !== 0) {

                    $scanFailed++;

                    $this->error(
                        '✗ Vulnerability scan gagal.'
                    );

                    $this->newLine();

                    continue;
                }

                $scanSuccess++;

                $this->info(
                    '✓ Vulnerability scan berhasil.'
                );

                /*
                 * =============================================
                 * CARI ASSESSMENT BARU
                 * =============================================
                 */

                $newAssessment =
                    VulnerabilityAssessment::query()
                        ->where(
                            'database_connection_id',
                            $connection->id
                        )
                        ->when(
                            $beforeAssessmentId,
                            function ($query) use (
                                $beforeAssessmentId
                            ) {

                                $query->where(
                                    'id',
                                    '>',
                                    $beforeAssessmentId
                                );
                            }
                        )
                        ->latest('id')
                        ->first();

                /*
                 * Fallback.
                 *
                 * Jika ini scan pertama, beforeAssessmentId
                 * kemungkinan null.
                 */
                if (! $newAssessment) {

                    $newAssessment =
                        VulnerabilityAssessment::query()
                            ->where(
                                'database_connection_id',
                                $connection->id
                            )
                            ->latest('id')
                            ->first();
                }

                /*
                 * =============================================
                 * ASSESSMENT TIDAK DITEMUKAN
                 * =============================================
                 */

                if (! $newAssessment) {

                    $alertFailed++;

                    $this->warn(
                        'Assessment baru tidak ditemukan. Alert dilewati.'
                    );

                    $this->newLine();

                    continue;
                }

                $this->line(
                    'Assessment baru : #'.
                    $newAssessment->id
                );

                /*
                 * =============================================
                 * GENERATE ALERTS
                 * =============================================
                 */

                try {

                    $alertExitCode =
                        $this->call(
                            'security:generate-alerts',
                            [
                                '--assessment' => $newAssessment->id,
                            ]
                        );

                    if ($alertExitCode === 0) {

                        $alertSuccess++;

                        $this->info(
                            '✓ Security alert generation berhasil.'
                        );

                    } else {

                        $alertFailed++;

                        $this->error(
                            '✗ Security alert generation gagal.'
                        );
                    }

                } catch (Throwable $e) {

                    $alertFailed++;

                    $this->reportConsoleException($e);

                    $this->error(
                        '✗ Alert error: '.
                        $this->safeConsoleExceptionMessage()
                    );
                }

            } catch (Throwable $e) {

                $scanFailed++;

                $this->reportConsoleException($e);

                $this->error(
                    '✗ Scan error: '.
                    $this->safeConsoleExceptionMessage()
                );
            }

            $this->newLine();
        }

        /*
         * =====================================================
         * SUMMARY
         * =====================================================
         */

        $this->line(
            '=============================================='
        );

        $this->info(
            'AUTO SECURITY MONITORING SELESAI'
        );

        $this->line(
            '=============================================='
        );

        $this->line(
            'Database             : '.
            $connections->count()
        );

        $this->line(
            'Scan Success         : '.
            $scanSuccess
        );

        $this->line(
            'Scan Failed          : '.
            $scanFailed
        );

        $this->line(
            'Alert Generation OK  : '.
            $alertSuccess
        );

        $this->line(
            'Alert Generation Fail: '.
            $alertFailed
        );

        $this->newLine();

        /*
         * =====================================================
         * RESULT
         * =====================================================
         */

        if (
            $scanFailed > 0
            || $alertFailed > 0
        ) {

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
