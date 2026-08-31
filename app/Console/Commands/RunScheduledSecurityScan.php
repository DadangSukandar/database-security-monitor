<?php

namespace App\Console\Commands;

use App\Models\DatabaseConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunScheduledSecurityScan extends Command
{
    /**
     * Nama command.
     */
    protected $signature = 'security:scheduled-scan
                            {--connection= : ID koneksi database tertentu}
                            {--force : Jalankan scan meskipun koneksi tidak aktif}';

    /**
     * Deskripsi command.
     */
    protected $description =
        'Menjalankan security vulnerability assessment secara otomatis';


    /**
     * Jalankan command.
     */
    public function handle(): int
    {
        $this->info(
            '=============================================='
        );

        $this->info(
            '   SCHEDULED SECURITY SCAN'
        );

        $this->info(
            '=============================================='
        );

        $this->newLine();


        /*
         * =====================================================
         * AMBIL DATABASE CONNECTION
         * =====================================================
         */

        $query = DatabaseConnection::query();


        /*
         * Jika connection ID diberikan,
         * hanya scan koneksi tersebut.
         */
        if ($this->option('connection')) {

            $query->where(
                'id',
                $this->option('connection')
            );

        } elseif (!$this->option('force')) {

            /*
             * Default:
             * hanya koneksi aktif.
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
         * Tidak ada koneksi.
         */
        if ($connections->isEmpty()) {

            $this->warn(
                'Tidak ada database connection yang dapat di-scan.'
            );

            return self::SUCCESS;
        }


        $this->info(
            'Ditemukan ' .
            $connections->count() .
            ' database connection.'
        );

        $this->newLine();


        /*
         * =====================================================
         * COUNTER
         * =====================================================
         */

        $success = 0;

        $failed = 0;


        /*
         * =====================================================
         * SCAN SETIAP CONNECTION
         * =====================================================
         */

        foreach ($connections as $connection) {

            $this->line(
                '----------------------------------------------'
            );

            $this->info(
                'Scanning: ' .
                ($connection->name ?? 'Unnamed')
            );

            $this->line(
                'ID: ' .
                $connection->id
            );

            $this->line(
                'Driver: ' .
                ($connection->driver ?? '-')
            );

            $this->newLine();


            try {

                /*
                 * Jalankan command scan utama.
                 *
                 * Command ini akan menggunakan
                 * VulnerabilityAssessmentController
                 * melalui endpoint yang sudah ada.
                 *
                 * Untuk tahap scheduler kita hanya
                 * menyiapkan orchestration terlebih dahulu.
                 */

                $this->call(
                    'security:scan-connection',
                    [
                        'connection' =>
                            $connection->id,
                    ]
                );


                $success++;


                $this->info(
                    '✓ Scan berhasil.'
                );

            } catch (Throwable $e) {

                $failed++;


                $this->error(
                    '✗ Scan gagal: ' .
                    $e->getMessage()
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
            'SCAN SELESAI'
        );

        $this->line(
            '=============================================='
        );

        $this->line(
            'Berhasil : ' .
            $success
        );

        $this->line(
            'Gagal    : ' .
            $failed
        );

        $this->newLine();


        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}