<?php

namespace App\Services;

use App\Enums\DatabaseConnectionFailureType;
use App\Exceptions\DatabaseConnectionException;
use App\Models\DatabaseConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseConnectorService
{
    /*
    |--------------------------------------------------------------------------
    | Connect
    |--------------------------------------------------------------------------
    */

    public function connect(
        DatabaseConnection $connection
    ): Connection {
        $db = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | Create Runtime Connection
            |--------------------------------------------------------------------------
            */

            $db = $this->connectionFromData([
                'driver' => $connection->driver,

                'host' => $connection->host,

                'port' => $connection->port,

                'database' => $connection->database,

                'username' => $connection->username,

                'password' => $connection
                    ->getDecryptedPassword(),

                'schema' => $connection->schema,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Enforce Read Only Session
            |--------------------------------------------------------------------------
            */

            $this->enforceReadOnlySession(
                $db,
                (string) $connection->driver
            );

            /*
            |--------------------------------------------------------------------------
            | Health Success
            |--------------------------------------------------------------------------
            |
            | Health tracking bersifat telemetry.
            | Jika penyimpanan health state gagal,
            | koneksi database yang sebenarnya berhasil
            | tidak boleh ikut dianggap gagal.
            |
            */

            $this->markHealthySafely(
                $connection
            );

            return $db;
        } catch (
            DatabaseConnectionException $exception
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Runtime Connection
            |--------------------------------------------------------------------------
            */

            if ($db instanceof Connection) {
                $this->release(
                    $db
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Health Failure
            |--------------------------------------------------------------------------
            */

            $this->markUnhealthySafely(
                $connection,
                $exception->failureType
            );

            /*
            |--------------------------------------------------------------------------
            | Preserve Typed Exception
            |--------------------------------------------------------------------------
            */

            throw $exception;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Runtime Connection
            |--------------------------------------------------------------------------
            |
            | connectionFromData() sudah cleanup jika proses PDO gagal.
            |
            | Tetapi jika connection berhasil dibuat lalu
            | enforceReadOnlySession() gagal, $db sudah tersedia.
            | Karena itu runtime connection harus dilepas di sini.
            |
            */

            if ($db instanceof Connection) {
                $this->release(
                    $db
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Classify Failure
            |--------------------------------------------------------------------------
            */

            $failureType =
                app(
                    DatabaseConnectionFailureClassifier::class
                )->classify(
                    $exception
                );

            /*
            |--------------------------------------------------------------------------
            | Health Failure
            |--------------------------------------------------------------------------
            */

            $this->markUnhealthySafely(
                $connection,
                $failureType
            );

            /*
            |--------------------------------------------------------------------------
            | Safe Typed Exception
            |--------------------------------------------------------------------------
            */

            throw new DatabaseConnectionException(
                $failureType,
                $exception
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Test Connection
    |--------------------------------------------------------------------------
    */

    public function test(
        DatabaseConnection $connection
    ): bool {
        $db = $this->connect(
            $connection
        );

        try {
            return true;
        } finally {
            $this->release(
                $db
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Connection From Data
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array{
     *     driver: string,
     *     host: string,
     *     port: int|string,
     *     database: string,
     *     username: string,
     *     password?: string|null,
     *     schema?: string|null
     * }  $data
     */
    public function connectionFromData(
        array $data
    ): Connection {
        /*
        |--------------------------------------------------------------------------
        | Connection Config
        |--------------------------------------------------------------------------
        */

        $config =
            $this->connectionConfig(
                $data
            );

        /*
        |--------------------------------------------------------------------------
        | Unique Runtime Name
        |--------------------------------------------------------------------------
        */

        $connectionName =
            $this->runtimeConnectionName(
                $data
            );

        /*
        |--------------------------------------------------------------------------
        | Register Runtime Configuration
        |--------------------------------------------------------------------------
        */

        Config::set(
            "database.connections.{$connectionName}",
            $config
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Existing Runtime Instance
        |--------------------------------------------------------------------------
        */

        DB::purge(
            $connectionName
        );

        try {
            /*
            |--------------------------------------------------------------------------
            | Establish Connection
            |--------------------------------------------------------------------------
            */

            $connection =
                DB::connection(
                    $connectionName
                );

            /*
            |--------------------------------------------------------------------------
            | Force PDO Connection
            |--------------------------------------------------------------------------
            */

            $connection->getPdo();

            return $connection;
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Failed Runtime Connection
            |--------------------------------------------------------------------------
            */

            $this->cleanupRuntimeConnection(
                $connectionName
            );

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Connection Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array{
     *     driver: string,
     *     host: string,
     *     port: int|string,
     *     database: string,
     *     username: string,
     *     password?: string|null,
     *     schema?: string|null
     * }  $data
     * @return array<string, mixed>
     */
    private function connectionConfig(
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Driver
        |--------------------------------------------------------------------------
        */

        $driver =
            strtolower(
                (string) $data['driver']
            );

        if (
            ! in_array(
                $driver,
                [
                    'mysql',
                    'pgsql',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Driver database tidak didukung.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Connection Timeout
        |--------------------------------------------------------------------------
        */

        $connectTimeout =
            max(
                1,
                (int) config(
                    'security.monitoring_connect_timeout',
                    5
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Base Config
        |--------------------------------------------------------------------------
        */

        $config = [
            'driver' => $driver,

            'host' => $data['host'],

            'port' => $data['port'],

            'database' => $data['database'],

            'username' => $data['username'],

            'password' => $data['password'] ?? '',

            'charset' => 'utf8',

            'prefix' => '',

            'prefix_indexes' => true,

            'schema' => ($data['schema'] ?? null)
                    ?: 'public',

            'sslmode' => config(
                'security.monitoring_sslmode',
                'prefer'
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | MySQL Timeout
        |--------------------------------------------------------------------------
        */

        if ($driver === 'mysql') {
            $config['options'] = [
                PDO::ATTR_TIMEOUT => $connectTimeout,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL Timeout
        |--------------------------------------------------------------------------
        */

        if ($driver === 'pgsql') {
            $config[
                'connect_timeout'
            ] = $connectTimeout;
        }

        return $config;
    }

    /*
    |--------------------------------------------------------------------------
    | Runtime Connection Name
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array{
     *     driver: string,
     *     host: string,
     *     port: int|string,
     *     database: string,
     *     username: string,
     *     password?: string|null,
     *     schema?: string|null
     * }  $data
     */
    private function runtimeConnectionName(
        array $data
    ): string {
        return 'monitoring_'.
            bin2hex(
                random_bytes(16)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Operation Scoped Connection
    |--------------------------------------------------------------------------
    */

    public function withConnection(
        DatabaseConnection $connection,
        callable $callback
    ): mixed {
        $db = $this->connect(
            $connection
        );

        try {
            return $callback(
                $db
            );
        } finally {
            $this->release(
                $db
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Release Runtime Connection
    |--------------------------------------------------------------------------
    */

    public function release(
        Connection $connection
    ): void {
        $name =
            $connection->getName();

        /*
        |--------------------------------------------------------------------------
        | Jangan Purge Connection Aplikasi
        |--------------------------------------------------------------------------
        */

        if (
            ! str_starts_with(
                $name,
                'monitoring_'
            )
        ) {
            return;
        }

        $this->cleanupRuntimeConnection(
            $name
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Runtime Cleanup
    |--------------------------------------------------------------------------
    */

    private function cleanupRuntimeConnection(
        string $name
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Disconnect / Purge
        |--------------------------------------------------------------------------
        */

        DB::purge(
            $name
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Credential-bearing Runtime Config
        |--------------------------------------------------------------------------
        */

        Config::set(
            "database.connections.{$name}",
            null
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Read Only Session
    |--------------------------------------------------------------------------
    */

    private function enforceReadOnlySession(
        Connection $connection,
        string $driver
    ): void {
        match ($driver) {
            'mysql' => $connection->statement(
                'SET SESSION TRANSACTION READ ONLY'
            ),

            'pgsql' => $connection->statement(
                'SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY'
            ),

            default => throw new RuntimeException(
                'Driver database tidak didukung.'
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Healthy Safely
    |--------------------------------------------------------------------------
    |
    | Health tracking adalah telemetry.
    |
    | Error pada penyimpanan telemetry tidak boleh
    | mengubah koneksi database yang sebenarnya berhasil
    | menjadi connection failure.
    |
    */

    private function markHealthySafely(
        DatabaseConnection $connection
    ): void {
        try {
            app(
                DatabaseConnectionHealthService::class
            )->markHealthy(
                $connection
            );
        } catch (Throwable $exception) {
            report(
                $exception
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Unhealthy Safely
    |--------------------------------------------------------------------------
    |
    | Jika database target gagal dihubungi,
    | exception asli tetap menjadi sumber kegagalan.
    |
    | Error saat menyimpan health state tidak boleh
    | menimpa DatabaseConnectionException tersebut.
    |
    */

    private function markUnhealthySafely(
        DatabaseConnection $connection,
        DatabaseConnectionFailureType $failureType
    ): void {
        try {
            app(
                DatabaseConnectionHealthService::class
            )->markUnhealthy(
                $connection,
                $failureType
            );
        } catch (Throwable $exception) {
            report(
                $exception
            );
        }
    }
}
