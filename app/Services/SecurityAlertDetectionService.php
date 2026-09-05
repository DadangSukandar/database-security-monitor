<?php

namespace App\Services;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Models\SecurityAlert;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SecurityAlertDetectionService
{
    public function analyze(DatabaseActivity $activity): array
    {
        $alerts = [];

        $query = strtoupper(
            trim($activity->query ?? '')
        );

        $action = strtoupper(
            trim($activity->action ?? '')
        );

        $table = strtolower(
            trim($activity->table_name ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | 1. FAILED QUERY
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper($activity->status ?? '') === 'FAILED'
        ) {
            $alerts[] = [
                'rule' => 'FAILED_QUERY',
                'severity' => 'MEDIUM',
                'title' => 'Database query gagal',
                'description' => 'Terdeteksi query database yang gagal dijalankan.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. DELETE
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'DELETE'
            || Str::startsWith($query, 'DELETE ')
        ) {
            $alerts[] = [
                'rule' => 'DELETE_OPERATION',
                'severity' => 'HIGH',
                'title' => 'DELETE operation terdeteksi',
                'description' => 'User menjalankan operasi DELETE pada database.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. UPDATE
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'UPDATE'
            || Str::startsWith($query, 'UPDATE ')
        ) {
            $alerts[] = [
                'rule' => 'UPDATE_OPERATION',
                'severity' => 'MEDIUM',
                'title' => 'UPDATE operation terdeteksi',
                'description' => 'User menjalankan operasi UPDATE pada database.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. DROP
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains($query, 'DROP TABLE')
            || Str::contains($query, 'DROP DATABASE')
            || Str::contains($query, 'DROP USER')
        ) {
            $alerts[] = [
                'rule' => 'DANGEROUS_DDL',
                'severity' => 'CRITICAL',
                'title' => 'Dangerous DDL operation',
                'description' => 'Terdeteksi operasi DROP yang berpotensi merusak database.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ALTER
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains($query, 'ALTER TABLE')
        ) {
            $alerts[] = [
                'rule' => 'ALTER_TABLE',
                'severity' => 'HIGH',
                'title' => 'ALTER TABLE terdeteksi',
                'description' => 'User melakukan perubahan struktur database.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6. GRANT / REVOKE
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains($query, 'GRANT ')
        ) {
            $alerts[] = [
                'rule' => 'GRANT_PRIVILEGE',
                'severity' => 'HIGH',
                'title' => 'Privilege diberikan',
                'description' => 'Terdeteksi pemberian privilege database.',
            ];
        }

        if (
            Str::contains($query, 'REVOKE ')
        ) {
            $alerts[] = [
                'rule' => 'REVOKE_PRIVILEGE',
                'severity' => 'HIGH',
                'title' => 'Privilege dicabut',
                'description' => 'Terdeteksi perubahan privilege database.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 7. SENSITIVE TABLE
        |--------------------------------------------------------------------------
        */

        $sensitiveTables = [
            'users',
            'customers',
            'customer',
            'employees',
            'employee',
            'passwords',
            'credentials',
            'payments',
            'transactions',
            'bank_accounts',
            'accounts',
        ];

        if (
            in_array($table, $sensitiveTables, true)
            && Str::startsWith($query, 'SELECT')
        ) {
            $alerts[] = [
                'rule' => 'SENSITIVE_TABLE_ACCESS',
                'severity' => 'HIGH',
                'title' => 'Sensitive table diakses',
                'description' => 'User mengakses tabel yang dikategorikan sebagai sensitive.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. LONG RUNNING QUERY
        |--------------------------------------------------------------------------
        */

        $executionTime =
            (int) ($activity->execution_time_ms ?? 0);

        if ($executionTime >= 5000) {
            $alerts[] = [
                'rule' => 'LONG_RUNNING_QUERY',
                'severity' => 'MEDIUM',
                'title' => 'Long running query',
                'description' => 'Query membutuhkan waktu eksekusi lebih dari 5 detik.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 9. SELECT *
        |--------------------------------------------------------------------------
        */

        if (
            Str::contains($query, 'SELECT *')
        ) {
            $alerts[] = [
                'rule' => 'SELECT_STAR',
                'severity' => 'LOW',
                'title' => 'SELECT * terdeteksi',
                'description' => 'Query mengambil seluruh kolom dari tabel.',
            ];
        }

        return $alerts;
    }

    public function scan(DatabaseActivity $activity): int
    {
        $alerts = $this->analyze($activity);

        foreach ($alerts as $alert) {

            $this->createOwnedAlert($activity, [
                'database_connection_id' => $activity->database_connection_id,

                'database_activity_id' => $activity->id,

                'database_name' => $activity->database_name,

                'username' => $activity->username,

                'client_ip' => $activity->client_ip,

                'table_name' => $activity->table_name,

                'action' => $activity->action,

                'query' => $activity->query,

                'rule' => $alert['rule'],

                'title' => $alert['title'],

                'description' => $alert['description'],

                'severity' => $alert['severity'],

                'status' => 'OPEN',

                'detected_at' => now(),
            ]);
        }

        return count($alerts);
    }

    private function createOwnedAlert(
        DatabaseActivity $activity,
        array $attributes
    ): SecurityAlert {
        $teamId = DatabaseConnection::query()
            ->whereKey($activity->database_connection_id)
            ->value('team_id');

        if ($teamId === null) {
            throw new InvalidArgumentException(
                'Cannot create security alert without trusted team ownership.'
            );
        }

        $alert = new SecurityAlert($attributes);

        $alert->team_id = (int) $teamId;

        $alert->save();

        return $alert;
    }
}
