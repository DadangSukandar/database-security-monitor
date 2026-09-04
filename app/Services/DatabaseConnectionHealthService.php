<?php

namespace App\Services;

use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use App\Models\DatabaseConnection;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionHealthService
{
    /*
    |--------------------------------------------------------------------------
    | Mark Healthy
    |--------------------------------------------------------------------------
    */

    public function markHealthy(
        DatabaseConnection $connection
    ): void {
        DB::transaction(
            function () use ($connection): void {
                /*
                |--------------------------------------------------------------------------
                | Lock Current State
                |--------------------------------------------------------------------------
                |
                | Recovery timestamp bergantung pada state sebelumnya.
                | Karena itu row dikunci selama transition diperiksa.
                |
                */

                $current =
                    DatabaseConnection::query()
                        ->whereKey(
                            $connection->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $wasUnhealthy =
                    $current->health_status ===
                    DatabaseConnectionHealthStatus::UNHEALTHY;

                $now = now();

                $attributes = [
                    'health_status' => DatabaseConnectionHealthStatus::HEALTHY,

                    'last_health_checked_at' => $now,

                    'last_connected_at' => $now,

                    'last_failure_type' => null,

                    'consecutive_failures' => 0,
                ];

                /*
                |--------------------------------------------------------------------------
                | Recovery Transition
                |--------------------------------------------------------------------------
                |
                | last_recovered_at hanya berubah saat:
                |
                | UNHEALTHY -> HEALTHY
                |
                | HEALTHY -> HEALTHY tidak boleh menghasilkan
                | recovery timestamp baru.
                |
                */

                if ($wasUnhealthy) {
                    $attributes[
                        'last_recovered_at'
                    ] = $now;
                }

                $current
                    ->forceFill(
                        $attributes
                    )
                    ->save();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Synchronize Caller Model
        |--------------------------------------------------------------------------
        */

        $connection->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Unhealthy
    |--------------------------------------------------------------------------
    */

    public function markUnhealthy(
        DatabaseConnection $connection,
        DatabaseConnectionFailureType $failureType
    ): void {
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Atomic Failure Increment
        |--------------------------------------------------------------------------
        |
        | Jangan gunakan:
        |
        | $connection->consecutive_failures + 1
        |
        | lalu save(), karena dua worker dapat membaca angka yang sama
        | dan salah satu increment hilang.
        |
        | Query increment menghasilkan operasi database:
        |
        | consecutive_failures = consecutive_failures + 1
        |
        | sehingga counter tidak bergantung pada snapshot model lama.
        |
        */

        DatabaseConnection::query()
            ->whereKey(
                $connection->getKey()
            )
            ->increment(
                'consecutive_failures',
                1,
                [
                    'health_status' => DatabaseConnectionHealthStatus::UNHEALTHY->value,

                    'last_health_checked_at' => $now,

                    'last_failed_at' => $now,

                    'last_failure_type' => $failureType->value,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Synchronize Caller Model
        |--------------------------------------------------------------------------
        */

        $connection->refresh();
    }
}
