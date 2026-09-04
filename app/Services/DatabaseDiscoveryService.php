<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredColumn;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use Throwable;

class DatabaseDiscoveryService
{
    /*
    |--------------------------------------------------------------------------
    | SCAN
    |--------------------------------------------------------------------------
    */

    public function scan(
        DatabaseConnection $connection
    ): array {
        $connector = app(
            DatabaseConnectorService::class
        );

        $db = $connector->connect(
            $connection
        );

        try {
            $driver = $connection->driver;

            /*
            |--------------------------------------------------------------------------
            | CONNECTION INFO
            |--------------------------------------------------------------------------
            */

            $databaseName =
                $db->getDatabaseName();

            $version =
                $this->getVersion(
                    $db,
                    $driver
                );

            /*
            |--------------------------------------------------------------------------
            | REMOVE OLD DISCOVERY
            |--------------------------------------------------------------------------
            */

            DiscoveredDatabase::where(
                'database_connection_id',
                $connection->id
            )->delete();

            /*
            |--------------------------------------------------------------------------
            | CREATE DATABASE RECORD
            |--------------------------------------------------------------------------
            */

            $discoveredDatabase =
                DiscoveredDatabase::create([
                    'database_connection_id' => $connection->id,

                    'name' => $databaseName,

                    'engine' => strtoupper($driver),

                    'version' => $version,
                ]);

            /*
            |--------------------------------------------------------------------------
            | DISCOVER TABLES
            |--------------------------------------------------------------------------
            */

            if ($driver === 'mysql') {
                $this->scanMySql(
                    $db,
                    $discoveredDatabase
                );
            } elseif ($driver === 'pgsql') {
                $this->scanPostgres(
                    $db,
                    $discoveredDatabase
                );
            } else {
                throw new \RuntimeException(
                    'Driver database tidak didukung: '.
                    $driver
                );
            }

            /*
            |--------------------------------------------------------------------------
            | RESULT
            |--------------------------------------------------------------------------
            */

            $tableCount =
                DiscoveredTable::where(
                    'discovered_database_id',
                    $discoveredDatabase->id
                )->count();

            $columnCount =
                DiscoveredColumn::whereIn(
                    'discovered_table_id',
                    DiscoveredTable::where(
                        'discovered_database_id',
                        $discoveredDatabase->id
                    )->pluck('id')
                )->count();

            return [
                'database' => $discoveredDatabase,

                'tables' => $tableCount,

                'columns' => $columnCount,
            ];
        } finally {
            $connector->release($db);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MYSQL
    |--------------------------------------------------------------------------
    */

    private function scanMySql(
        $db,
        DiscoveredDatabase $database
    ): void {

        $databaseName =
            $db->getDatabaseName();

        $tables = $db->select(
            '
            SELECT
                TABLE_SCHEMA,
                TABLE_NAME,
                TABLE_TYPE,
                TABLE_ROWS
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME
            ',
            [
                $databaseName,
            ]
        );

        foreach ($tables as $tableInfo) {

            $table =
                DiscoveredTable::create([
                    'discovered_database_id' => $database->id,

                    'schema_name' => $tableInfo->TABLE_SCHEMA,

                    'name' => $tableInfo->TABLE_NAME,

                    'type' => $tableInfo->TABLE_TYPE,

                    'estimated_rows' => $tableInfo->TABLE_ROWS !== null
                            ? (int) $tableInfo->TABLE_ROWS
                            : null,
                ]);

            $columns =
                $db->select(
                    "
                    SELECT
                        c.COLUMN_NAME,
                        c.DATA_TYPE,
                        c.COLUMN_TYPE,
                        c.IS_NULLABLE,
                        c.COLUMN_DEFAULT,
                        CASE
                            WHEN k.COLUMN_NAME IS NOT NULL
                            THEN 1
                            ELSE 0
                        END AS IS_PRIMARY
                    FROM information_schema.COLUMNS c

                    LEFT JOIN (
                        SELECT
                            COLUMN_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND CONSTRAINT_NAME = 'PRIMARY'
                    ) k
                    ON k.COLUMN_NAME = c.COLUMN_NAME

                    WHERE c.TABLE_SCHEMA = ?
                    AND c.TABLE_NAME = ?

                    ORDER BY c.ORDINAL_POSITION
                    ",
                    [
                        $databaseName,
                        $table->name,
                        $databaseName,
                        $table->name,
                    ]
                );

            foreach ($columns as $column) {

                DiscoveredColumn::create([
                    'discovered_table_id' => $table->id,

                    'name' => $column->COLUMN_NAME,

                    'data_type' => $column->DATA_TYPE,

                    'column_type' => $column->COLUMN_TYPE,

                    'is_nullable' => $column->IS_NULLABLE,

                    'default_value' => $column->COLUMN_DEFAULT,

                    'is_primary' => (bool) $column->IS_PRIMARY,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POSTGRESQL
    |--------------------------------------------------------------------------
    */

    private function scanPostgres(
        $db,
        DiscoveredDatabase $database
    ): void {

        $tables = $db->select(
            "
            SELECT
                table_schema,
                table_name,
                table_type
            FROM information_schema.tables
            WHERE table_schema NOT IN (
                'pg_catalog',
                'information_schema'
            )
            AND table_type = 'BASE TABLE'
            ORDER BY table_schema, table_name
            "
        );

        foreach ($tables as $tableInfo) {

            $estimatedRows =
                $this->postgresEstimatedRows(
                    $db,
                    $tableInfo->table_schema,
                    $tableInfo->table_name
                );

            $table =
                DiscoveredTable::create([
                    'discovered_database_id' => $database->id,

                    'schema_name' => $tableInfo->table_schema,

                    'name' => $tableInfo->table_name,

                    'type' => $tableInfo->table_type,

                    'estimated_rows' => $estimatedRows,
                ]);

            $columns =
                $db->select(
                    "
                    SELECT
                        c.column_name,
                        c.data_type,
                        c.is_nullable,
                        c.column_default,
                        CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM information_schema.table_constraints tc
                                JOIN information_schema.key_column_usage kcu
                                  ON tc.constraint_name =
                                     kcu.constraint_name
                                 AND tc.table_schema =
                                     kcu.table_schema
                                 AND tc.table_name =
                                     kcu.table_name
                                WHERE tc.constraint_type = 'PRIMARY KEY'
                                  AND kcu.table_schema =
                                      c.table_schema
                                  AND kcu.table_name =
                                      c.table_name
                                  AND kcu.column_name =
                                      c.column_name
                            )
                            THEN true
                            ELSE false
                        END AS is_primary

                    FROM information_schema.columns c

                    WHERE c.table_schema = ?
                    AND c.table_name = ?

                    ORDER BY c.ordinal_position
                    ",
                    [
                        $table->schema_name,
                        $table->name,
                    ]
                );

            foreach ($columns as $column) {

                DiscoveredColumn::create([
                    'discovered_table_id' => $table->id,

                    'name' => $column->column_name,

                    'data_type' => $column->data_type,

                    'column_type' => $column->data_type,

                    'is_nullable' => $column->is_nullable,

                    'default_value' => $column->column_default,

                    'is_primary' => (bool) $column->is_primary,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VERSION
    |--------------------------------------------------------------------------
    */

    private function getVersion(
        $db,
        string $driver
    ): ?string {

        try {

            if ($driver === 'mysql') {

                $result =
                    $db->selectOne(
                        'SELECT VERSION() AS version'
                    );

                return $result->version ?? null;
            }

            if ($driver === 'pgsql') {

                $result =
                    $db->selectOne(
                        'SELECT version() AS version'
                    );

                return $result->version ?? null;
            }

        } catch (Throwable) {

            return null;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | POSTGRES ROW ESTIMATE
    |--------------------------------------------------------------------------
    */

    private function postgresEstimatedRows(
        $db,
        string $schema,
        string $table
    ): ?int {

        try {

            $result =
                $db->selectOne(
                    '
                    SELECT
                        reltuples::BIGINT AS estimate
                    FROM pg_class c
                    JOIN pg_namespace n
                      ON n.oid = c.relnamespace
                    WHERE n.nspname = ?
                    AND c.relname = ?
                    ',
                    [
                        $schema,
                        $table,
                    ]
                );

            if (
                $result &&
                $result->estimate !== null
            ) {

                return max(
                    0,
                    (int) $result->estimate
                );
            }

        } catch (Throwable) {

            return null;
        }

        return null;
    }
}
