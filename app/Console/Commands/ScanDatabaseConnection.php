<?php

namespace App\Console\Commands;

use App\Console\Concerns\HandlesSafeConsoleExceptions;
use App\Models\DatabaseConnection;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use App\Services\DatabaseConnectorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScanDatabaseConnection extends Command
{
    use HandlesSafeConsoleExceptions;

    /**
     * Command signature.
     */
    protected $signature = 'security:scan-connection
                            {connection : ID database connection yang akan di-scan}';

    /**
     * Command description.
     */
    protected $description =
        'Menjalankan vulnerability assessment pada satu database connection';

    /**
     * Execute command.
     */
    public function handle(
        DatabaseConnectorService $connector
    ): int {
        $connectionId =
            $this->argument('connection');

        /*
        |--------------------------------------------------------------------------
        | DATABASE CONNECTION
        |--------------------------------------------------------------------------
        */

        $databaseConnection =
            DatabaseConnection::find(
                $connectionId
            );

        if (! $databaseConnection) {
            $this->error(
                'Database connection dengan ID '.
                $connectionId.
                ' tidak ditemukan.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Database : '.
            ($databaseConnection->name ?? '-')
        );

        $this->info(
            'Driver   : '.
            ($databaseConnection->driver ?? '-')
        );

        $this->info(
            'ID       : '.
            $databaseConnection->id
        );

        $this->newLine();

        try {
            /*
            |--------------------------------------------------------------------------
            | CONNECT
            |--------------------------------------------------------------------------
            */

            $this->info(
                'Menghubungkan ke database...'
            );

            $assessment =
                $connector->withConnection(
                    $databaseConnection,
                    function ($db) use (
                        $databaseConnection
                    ) {
                        $this->info(
                            '✓ Koneksi berhasil.'
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE ASSESSMENT
                        |--------------------------------------------------------------------------
                        */

                        return DB::transaction(
                            function () use (
                                $databaseConnection,
                                $db
                            ) {
                                /*
                                |--------------------------------------------------------------------------
                                | BUAT ASSESSMENT
                                |--------------------------------------------------------------------------
                                */

                                $assessment =
                                    VulnerabilityAssessment::create([
                                        'database_connection_id' => $databaseConnection->id,

                                        'database_name' => $db->getDatabaseName(),

                                        'score' => 100,

                                        'critical_count' => 0,

                                        'high_count' => 0,

                                        'medium_count' => 0,

                                        'low_count' => 0,

                                        'status' => 'SCANNING',

                                        'scanned_at' => now(),
                                    ]);

                                /*
                                |--------------------------------------------------------------------------
                                | FINDINGS
                                |--------------------------------------------------------------------------
                                */

                                $findings = [];

                                /*
                                |--------------------------------------------------------------------------
                                | MYSQL
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    $databaseConnection->driver ===
                                    'mysql'
                                ) {
                                    $findings =
                                        $this->scanMySQL(
                                            $db
                                        );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | POSTGRESQL
                                |--------------------------------------------------------------------------
                                */

                                elseif (
                                    $databaseConnection->driver ===
                                    'pgsql'
                                ) {
                                    $findings =
                                        $this->scanPostgreSQL(
                                            $db
                                        );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | UNSUPPORTED DRIVER
                                |--------------------------------------------------------------------------
                                */

                                else {
                                    throw new \RuntimeException(
                                        'Database driver tidak didukung: '.
                                        $databaseConnection->driver
                                    );
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | COUNTER
                                |--------------------------------------------------------------------------
                                */

                                $critical = 0;
                                $high = 0;
                                $medium = 0;
                                $low = 0;

                                /*
                                |--------------------------------------------------------------------------
                                | SAVE FINDINGS
                                |--------------------------------------------------------------------------
                                */

                                foreach (
                                    $findings as $finding
                                ) {
                                    $severity =
                                        strtoupper(
                                            $finding[
                                                'severity'
                                            ]
                                            ?? 'LOW'
                                        );

                                    $ruleCode =
                                        $finding[
                                            'rule_code'
                                        ]
                                        ?? 'DATABASE-GENERAL-001';

                                    VulnerabilityFinding::create([
                                        'vulnerability_assessment_id' => $assessment->id,

                                        'rule_code' => $ruleCode,

                                        'category' => $finding[
                                                'category'
                                            ]
                                            ?? 'DATABASE',

                                        'severity' => $severity,

                                        'title' => $finding[
                                                'title'
                                            ]
                                            ?? 'Security Finding',

                                        'description' => $finding[
                                                'description'
                                            ]
                                            ?? null,

                                        'recommendation' => $finding[
                                                'recommendation'
                                            ]
                                            ?? null,

                                        'database_name' => $finding[
                                                'database_name'
                                            ]
                                            ?? $db
                                                ->getDatabaseName(),

                                        'username' => $finding[
                                                'username'
                                            ]
                                            ?? null,

                                        'host' => $finding[
                                                'host'
                                            ]
                                            ?? null,

                                        'evidence' => $finding[
                                                'evidence'
                                            ]
                                            ?? null,
                                    ]);

                                    /*
                                    |--------------------------------------------------------------------------
                                    | SEVERITY COUNTER
                                    |--------------------------------------------------------------------------
                                    */

                                    switch ($severity) {
                                        case 'CRITICAL':
                                            $critical++;

                                            break;

                                        case 'HIGH':
                                            $high++;

                                            break;

                                        case 'MEDIUM':
                                            $medium++;

                                            break;

                                        case 'LOW':
                                        default:
                                            $low++;

                                            break;
                                    }
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | CALCULATE SCORE
                                |--------------------------------------------------------------------------
                                */

                                $score =
                                    $this->calculateScore(
                                        $critical,
                                        $high,
                                        $medium,
                                        $low
                                    );

                                /*
                                |--------------------------------------------------------------------------
                                | UPDATE ASSESSMENT
                                |--------------------------------------------------------------------------
                                */

                                $assessment->update([
                                    'score' => $score,

                                    'critical_count' => $critical,

                                    'high_count' => $high,

                                    'medium_count' => $medium,

                                    'low_count' => $low,

                                    'status' => 'COMPLETED',

                                    'scanned_at' => now(),
                                ]);

                                return $assessment;
                            }
                        );
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | RESULT
            |--------------------------------------------------------------------------
            |
            | Sampai di sini runtime monitoring connection
            | sudah otomatis di-release oleh withConnection().
            |
            */

            $this->newLine();

            $this->info(
                '=============================================='
            );

            $this->info(
                'SCAN BERHASIL'
            );

            $this->info(
                '=============================================='
            );

            $this->line(
                'Assessment ID : '.
                $assessment->id
            );

            $this->line(
                'Database      : '.
                $assessment->database_name
            );

            $this->line(
                'Score         : '.
                $assessment->score.
                '/100'
            );

            $this->line(
                'Critical      : '.
                $assessment->critical_count
            );

            $this->line(
                'High          : '.
                $assessment->high_count
            );

            $this->line(
                'Medium        : '.
                $assessment->medium_count
            );

            $this->line(
                'Low           : '.
                $assessment->low_count
            );

            $this->line(
                'Status        : '.
                $assessment->status
            );

            $this->newLine();

            return self::SUCCESS;
        } catch (Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | SAFE ERROR HANDLING
            |--------------------------------------------------------------------------
            */

            $this->reportConsoleException(
                $e
            );

            $this->newLine();

            $this->error(
                'SCAN GAGAL'
            );

            $this->error(
                $this->safeConsoleExceptionMessage()
            );

            return self::FAILURE;
        }
    }

    /**
     * =========================================================
     * MYSQL SCAN
     * =========================================================
     */
    private function scanMySQL($db): array
    {
        $findings = [];

        try {

            $columns = $this->getMySQLUserColumns(
                $db
            );

            $selectColumns = [];

            $requiredColumns = [

                'User',

                'Host',

                'Super_priv',

                'Grant_priv',

                'account_locked',

                'password_expired',

                'File_priv',
            ];

            foreach (
                $requiredColumns as $column
            ) {

                foreach (
                    $columns as $realColumn
                ) {

                    if (
                        strtolower($realColumn)
                        === strtolower($column)
                    ) {

                        $selectColumns[] =
                            $realColumn;

                        break;
                    }
                }
            }

            if (
                empty($selectColumns)
            ) {

                return [];
            }

            $users =
                $db->table('mysql.user')
                    ->select($selectColumns)
                    ->get();

            foreach (
                $users as $user
            ) {

                $username =
                    $this->getObjectValue(
                        $user,
                        'User'
                    );

                $host =
                    $this->getObjectValue(
                        $user,
                        'Host'
                    );

                if (
                    $username === null
                ) {

                    continue;
                }

                /*
                 * SUPER
                 */
                $super =
                    $this->getObjectValue(
                        $user,
                        'Super_priv'
                    );

                if (
                    strtoupper(
                        (string) $super
                    ) === 'Y'
                ) {

                    $findings[] = [

                        'rule_code' => 'MYSQL-ACCESS-001',

                        'category' => 'ACCESS_CONTROL',

                        'severity' => 'HIGH',

                        'title' => 'MySQL SUPER privilege detected',

                        'description' => 'User "'.
                            $username.
                            '"@"'.
                            $host.
                            '" memiliki SUPER privilege.',

                        'recommendation' => 'Batasi SUPER privilege hanya untuk administrator database.',

                        'database_name' => $db->getDatabaseName(),

                        'username' => $username,

                        'host' => $host,

                        'evidence' => 'SUPER privilege = Y',
                    ];
                }

                /*
                 * GRANT
                 */
                $grant =
                    $this->getObjectValue(
                        $user,
                        'Grant_priv'
                    );

                if (
                    strtoupper(
                        (string) $grant
                    ) === 'Y'
                ) {

                    $findings[] = [

                        'rule_code' => 'MYSQL-ACCESS-002',

                        'category' => 'ACCESS_CONTROL',

                        'severity' => 'HIGH',

                        'title' => 'User memiliki GRANT privilege',

                        'description' => 'User "'.
                            $username.
                            '" dapat memberikan privilege kepada user lain.',

                        'recommendation' => 'Batasi GRANT privilege hanya kepada administrator.',

                        'database_name' => $db->getDatabaseName(),

                        'username' => $username,

                        'host' => $host,

                        'evidence' => 'Grant_priv = Y',
                    ];
                }

                /*
                 * HOST %
                 */
                if (
                    $host === '%'
                ) {

                    $findings[] = [

                        'rule_code' => 'MYSQL-NETWORK-001',

                        'category' => 'NETWORK_SECURITY',

                        'severity' => 'MEDIUM',

                        'title' => 'Database account dapat login dari semua host',

                        'description' => 'User "'.
                            $username.
                            '" menggunakan Host "%".',

                        'recommendation' => 'Batasi host login ke alamat jaringan yang diperlukan.',

                        'database_name' => $db->getDatabaseName(),

                        'username' => $username,

                        'host' => $host,

                        'evidence' => 'Host = %',
                    ];
                }

                /*
                 * PASSWORD EXPIRED
                 */
                $passwordExpired =
                    $this->getObjectValue(
                        $user,
                        'password_expired'
                    );

                if (
                    strtoupper(
                        (string) $passwordExpired
                    ) === 'Y'
                ) {

                    $findings[] = [

                        'rule_code' => 'MYSQL-AUTH-001',

                        'category' => 'AUTHENTICATION',

                        'severity' => 'MEDIUM',

                        'title' => 'Password account telah expired',

                        'description' => 'Password user "'.
                            $username.
                            '" telah expired.',

                        'recommendation' => 'Perbarui password account atau nonaktifkan account yang tidak digunakan.',

                        'database_name' => $db->getDatabaseName(),

                        'username' => $username,

                        'host' => $host,

                        'evidence' => 'password_expired = Y',
                    ];
                }

                /*
                 * FILE PRIVILEGE
                 */
                $filePrivilege =
                    $this->getObjectValue(
                        $user,
                        'File_priv'
                    );

                if (
                    strtoupper(
                        (string) $filePrivilege
                    ) === 'Y'
                ) {

                    $findings[] = [

                        'rule_code' => 'MYSQL-PRIVILEGE-001',

                        'category' => 'PRIVILEGE',

                        'severity' => 'HIGH',

                        'title' => 'FILE privilege terdeteksi',

                        'description' => 'User "'.
                            $username.
                            '" memiliki FILE privilege.',

                        'recommendation' => 'Cabut FILE privilege jika tidak diperlukan.',

                        'database_name' => $db->getDatabaseName(),

                        'username' => $username,

                        'host' => $host,

                        'evidence' => 'File_priv = Y',
                    ];
                }
            }

        } catch (Throwable $e) {

            throw $e;
        }

        return $findings;
    }

    /**
     * =========================================================
     * POSTGRESQL SCAN
     * =========================================================
     */
    private function scanPostgreSQL($db): array
    {
        $findings = [];

        $users = $db->select(
            '
            SELECT
                rolname,
                rolsuper,
                rolcreaterole,
                rolcreatedb,
                rolcanlogin
            FROM pg_roles
            '
        );

        foreach (
            $users as $user
        ) {

            /*
             * SUPERUSER
             */
            if (
                $user->rolsuper
            ) {

                $findings[] = [

                    'rule_code' => 'PGSQL-ACCESS-001',

                    'category' => 'ACCESS_CONTROL',

                    'severity' => 'HIGH',

                    'title' => 'PostgreSQL superuser detected',

                    'description' => 'Role "'.
                        $user->rolname.
                        '" memiliki SUPERUSER privilege.',

                    'recommendation' => 'Batasi superuser hanya kepada administrator database.',

                    'database_name' => $db->getDatabaseName(),

                    'username' => $user->rolname,

                    'host' => null,

                    'evidence' => 'rolsuper = true',
                ];
            }

            /*
             * CREATE ROLE
             */
            if (
                $user->rolcreaterole
            ) {

                $findings[] = [

                    'rule_code' => 'PGSQL-PRIVILEGE-001',

                    'category' => 'PRIVILEGE',

                    'severity' => 'MEDIUM',

                    'title' => 'Role dapat membuat role lain',

                    'description' => 'Role "'.
                        $user->rolname.
                        '" dapat membuat role PostgreSQL.',

                    'recommendation' => 'Batasi CREATEROLE hanya kepada administrator.',

                    'database_name' => $db->getDatabaseName(),

                    'username' => $user->rolname,

                    'host' => null,

                    'evidence' => 'rolcreaterole = true',
                ];
            }

            /*
             * CREATE DATABASE
             */
            if (
                $user->rolcreatedb
            ) {

                $findings[] = [

                    'rule_code' => 'PGSQL-PRIVILEGE-002',

                    'category' => 'PRIVILEGE',

                    'severity' => 'MEDIUM',

                    'title' => 'Role dapat membuat database',

                    'description' => 'Role "'.
                        $user->rolname.
                        '" dapat membuat database.',

                    'recommendation' => 'Batasi CREATEDB kepada account administrator.',

                    'database_name' => $db->getDatabaseName(),

                    'username' => $user->rolname,

                    'host' => null,

                    'evidence' => 'rolcreatedb = true',
                ];
            }
        }

        return $findings;
    }

    /**
     * =========================================================
     * MYSQL USER COLUMNS
     * =========================================================
     */
    private function getMySQLUserColumns(
        $db
    ): array {

        $rows =
            $db->select(
                "
                SELECT COLUMN_NAME
                FROM information_schema.columns
                WHERE TABLE_SCHEMA = 'mysql'
                AND TABLE_NAME = 'user'
                ORDER BY ORDINAL_POSITION
                "
            );

        return collect($rows)
            ->map(
                fn ($row) => $row->COLUMN_NAME
            )
            ->values()
            ->toArray();
    }

    /**
     * =========================================================
     * OBJECT VALUE
     * =========================================================
     */
    private function getObjectValue(
        object $object,
        string $property
    ) {

        if (
            isset(
                $object->{$property}
            )
        ) {

            return $object->{$property};
        }

        foreach (
            get_object_vars($object) as $key => $value
        ) {

            if (
                strtolower($key)
                === strtolower($property)
            ) {

                return $value;
            }
        }

        return null;
    }

    /**
     * =========================================================
     * SCORE
     * =========================================================
     */
    private function calculateScore(
        int $critical,
        int $high,
        int $medium,
        int $low
    ): int {

        $deduction =
            ($critical * 30) +
            ($high * 15) +
            ($medium * 7) +
            ($low * 2);

        $score =
            100 - $deduction;

        return max(
            0,
            min(
                100,
                $score
            )
        );
    }
}
