<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseConnectorService
{
    public function connect(DatabaseConnection $connection)
    {
        if (!in_array($connection->driver, ['mysql', 'pgsql'])) {
            throw new RuntimeException(
                'Driver database tidak didukung.'
            );
        }

        $config = [
            'driver' => $connection->driver,

            'host' => $connection->host,

            'port' => $connection->port,

            'database' => $connection->database,

            'username' => $connection->username,

            'password' => $connection->getDecryptedPassword(),

            'charset' => 'utf8',

            'prefix' => '',

            'prefix_indexes' => true,

            'schema' => $connection->schema ?: 'public',

            'sslmode' => 'prefer',
        ];

        Config::set(
            'database.connections.monitoring',
            $config
        );

        DB::purge('monitoring');

        return DB::connection('monitoring');
    }

    public function test(
        DatabaseConnection $connection
    ): bool {
        $db = $this->connect($connection);

        $db->getPdo();

        return true;
    }

    public function connectionFromData(array $data)
    {
        $config = [
            'driver' => $data['driver'],
            'host' => $data['host'],
            'port' => $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => $data['schema'] ?? 'public',
            'sslmode' => 'prefer',
        ];

        Config::set(
            'database.connections.monitoring',
            $config
        );

        DB::purge('monitoring');

        return DB::connection('monitoring');
    }
}