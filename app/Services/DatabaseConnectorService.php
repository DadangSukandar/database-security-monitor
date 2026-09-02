<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseConnectorService
{
    public function connect(DatabaseConnection $connection): Connection
    {
        $db = $this->connectionFromData([
            'driver' => $connection->driver,
            'host' => $connection->host,
            'port' => $connection->port,
            'database' => $connection->database,
            'username' => $connection->username,
            'password' => $connection->getDecryptedPassword(),
            'schema' => $connection->schema,
        ]);

        $this->enforceReadOnlySession($db, (string) $connection->driver);

        return $db;
    }

    public function test(DatabaseConnection $connection): bool
    {
        $this->connect($connection)->getPdo();

        return true;
    }

    /**
     * @param  array{driver: string, host: string, port: int|string, database: string, username: string, password?: string|null, schema?: string|null}  $data
     */
    public function connectionFromData(array $data): Connection
    {
        $driver = strtolower((string) $data['driver']);

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            throw new RuntimeException('Driver database tidak didukung.');
        }

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
            'schema' => ($data['schema'] ?? null) ?: 'public',
            'sslmode' => config('security.monitoring_sslmode', 'prefer'),
        ];

        Config::set('database.connections.monitoring', $config);
        DB::purge('monitoring');

        $connection = DB::connection('monitoring');
        $connection->getPdo();

        return $connection;
    }

    private function enforceReadOnlySession(Connection $connection, string $driver): void
    {
        match ($driver) {
            'mysql' => $connection->statement('SET SESSION TRANSACTION READ ONLY'),
            'pgsql' => $connection->statement('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY'),
            default => throw new RuntimeException('Driver database tidak didukung.'),
        };
    }
}
