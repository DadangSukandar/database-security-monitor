<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'security_alerts',
                'investigation_started_at'
            )
        ) {
            Schema::table(
                'security_alerts',
                function (Blueprint $table): void {
                    $table
                        ->timestamp('investigation_started_at')
                        ->nullable();
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill Active Investigations
        |--------------------------------------------------------------------------
        |
        | Sebelum kolom investigation_started_at tersedia, lifecycle tetap
        | mencatat START_INVESTIGATION ke security_alert_histories.
        |
        | Karena itu alert yang saat migration dijalankan masih berada pada
        | status INVESTIGATING dapat direkonstruksi dari history terakhirnya.
        |
        | Kita sengaja TIDAK melakukan backfill ke alert OPEN, karena alert
        | tersebut mungkin sudah pernah di-reopen dan investigation timestamp
        | dari lifecycle sebelumnya tidak boleh masuk ke lifecycle baru.
        |
        */

        if (
            Schema::hasTable('security_alert_histories')
            && Schema::hasColumn(
                'security_alerts',
                'investigation_started_at'
            )
        ) {
            $alertIds = DB::table('security_alerts')
                ->where('status', 'INVESTIGATING')
                ->pluck('id');

            foreach ($alertIds as $alertId) {
                $startedAt = DB::table(
                    'security_alert_histories'
                )
                    ->where(
                        'security_alert_id',
                        $alertId
                    )
                    ->where(
                        'action',
                        'START_INVESTIGATION'
                    )
                    ->orderByDesc('id')
                    ->value('created_at');

                if ($startedAt === null) {
                    continue;
                }

                DB::table('security_alerts')
                    ->where('id', $alertId)
                    ->whereNull(
                        'investigation_started_at'
                    )
                    ->update([
                        'investigation_started_at' => $startedAt,
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'security_alerts',
                'investigation_started_at'
            )
        ) {
            Schema::table(
                'security_alerts',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'investigation_started_at'
                    );
                }
            );
        }
    }
};
