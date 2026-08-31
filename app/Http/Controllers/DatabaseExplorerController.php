<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectorService;
use App\Services\DatabaseActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseExplorerController extends Controller
{
    public function show(
        Request $request,
        DatabaseConnection $databaseConnection,
        string $table,
        DatabaseConnectorService $connector,
        DatabaseActivityLogger $activityLogger
    ) {
        try {

            /*
            |--------------------------------------------------------------------------
            | Connect ke database target
            |--------------------------------------------------------------------------
            */

            $db = $connector->connect(
                $databaseConnection
            );


            /*
            |--------------------------------------------------------------------------
            | Validasi table
            |--------------------------------------------------------------------------
            */

            $tableExists = $this->tableExists(
                $db,
                $databaseConnection->driver,
                $table
            );

            abort_unless(
                $tableExists,
                404,
                'Table tidak ditemukan.'
            );


            /*
            |--------------------------------------------------------------------------
            | Column information
            |--------------------------------------------------------------------------
            */

            $columns = $this->getColumns(
                $db,
                $databaseConnection->driver,
                $table
            );


            /*
            |--------------------------------------------------------------------------
            | Sensitive Data Findings
            |--------------------------------------------------------------------------
            |
            | Ambil hasil discovery dari database aplikasi Laravel.
            |
            */

            $sensitiveColumns =
                $this->getSensitiveColumns(
                    $databaseConnection,
                    $table
                );


            /*
            |--------------------------------------------------------------------------
            | Tambahkan informasi sensitive ke setiap column
            |--------------------------------------------------------------------------
            */

            foreach ($columns as &$column) {

                $columnName = $column['name'];

                if (
                    isset(
                        $sensitiveColumns[$columnName]
                    )
                ) {

                    $finding =
                        $sensitiveColumns[$columnName];

                    $column['is_sensitive'] = true;

                    $column['category'] =
                        $finding->category;

                    $column['risk_level'] =
                        $finding->risk_level;

                    $column['rule_name'] =
                        $finding->rule_name;

                } else {

                    $column['is_sensitive'] = false;

                    $column['category'] = null;

                    $column['risk_level'] = null;

                    $column['rule_name'] = null;
                }
            }

            unset($column);


            /*
            |--------------------------------------------------------------------------
            | Query
            |--------------------------------------------------------------------------
            */

            $query = $db->table($table);


            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            $search = $request->input(
                'search'
            );


            /*
            |--------------------------------------------------------------------------
            | Per Page
            |--------------------------------------------------------------------------
            */

            $perPage = min(
                max(
                    (int) $request->input(
                        'per_page',
                        20
                    ),
                    10
                ),
                100
            );


            /*
            |--------------------------------------------------------------------------
            | Ambil data
            |--------------------------------------------------------------------------
            */

            $startTime = microtime(true);

            try {

                $rows = $query
                    ->paginate($perPage)
                    ->withQueryString();

                $executionTimeMs = (int) round(
                    (
                        microtime(true)
                        - $startTime
                    ) * 1000
                );


                /*
                |--------------------------------------------------------------------------
                | Mask sensitive data
                |--------------------------------------------------------------------------
                */

                $rows->getCollection()->transform(
                    function ($row) use (
                        $sensitiveColumns
                    ) {

                        $row = (array) $row;

                        foreach (
                            $sensitiveColumns
                            as $columnName => $finding
                        ) {

                            if (
                                array_key_exists(
                                    $columnName,
                                    $row
                                )
                            ) {

                                $row[$columnName] =
                                    $this->maskValue(
                                        $row[$columnName]
                                    );
                            }
                        }

                        return $row;
                    }
                );


                $activityLogger->success(
                    $databaseConnection,
                    'SELECT * FROM ' . $table,
                    'SELECT',
                    $table,
                    $executionTimeMs
                );

            } catch (Throwable $e) {

                $executionTimeMs = (int) round(
                    (
                        microtime(true)
                        - $startTime
                    ) * 1000
                );


                $activityLogger->failed(
                    $databaseConnection,
                    'SELECT * FROM ' . $table,
                    'SELECT',
                    $table,
                    $e,
                    $executionTimeMs
                );

                throw $e;
            }


            /*
            |--------------------------------------------------------------------------
            | Total Rows
            |--------------------------------------------------------------------------
            */

            $countStart = microtime(true);

            try {

                $totalRows = $db
                    ->table($table)
                    ->count();

                $countExecutionTime = (int) round(
                    (
                        microtime(true)
                        - $countStart
                    ) * 1000
                );


                $activityLogger->success(
                    $databaseConnection,
                    'SELECT COUNT(*) FROM ' . $table,
                    'SELECT',
                    $table,
                    $countExecutionTime
                );

            } catch (Throwable $e) {

                $countExecutionTime = (int) round(
                    (
                        microtime(true)
                        - $countStart
                    ) * 1000
                );


                $activityLogger->failed(
                    $databaseConnection,
                    'SELECT COUNT(*) FROM ' . $table,
                    'SELECT',
                    $table,
                    $e,
                    $countExecutionTime
                );

                throw $e;
            }


            /*
            |--------------------------------------------------------------------------
            | View
            |--------------------------------------------------------------------------
            */

            return view(
                'database-explorer.show',
                compact(
                    'databaseConnection',
                    'table',
                    'columns',
                    'rows',
                    'totalRows',
                    'search',
                    'perPage',
                    'sensitiveColumns'
                )
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'explorer' =>
                    'Gagal membaca table: ' .
                    $e->getMessage()
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Sensitive Columns
    |--------------------------------------------------------------------------
    */

    private function getSensitiveColumns(
        DatabaseConnection $databaseConnection,
        string $table
    ) {

        return DB::table(
            'sensitive_data_findings'
        )
            ->join(
                'discovered_columns',
                'discovered_columns.id',
                '=',
                'sensitive_data_findings.discovered_column_id'
            )
            ->join(
                'discovered_tables',
                'discovered_tables.id',
                '=',
                'discovered_columns.discovered_table_id'
            )
            ->join(
                'discovered_databases',
                'discovered_databases.id',
                '=',
                'discovered_tables.discovered_database_id'
            )
            ->where(
                'discovered_databases.database_connection_id',
                $databaseConnection->id
            )
            ->where(
                'discovered_tables.name',
                $table
            )
            ->select([
                'discovered_columns.name',
                'sensitive_data_findings.category',
                'sensitive_data_findings.risk_level',
                'sensitive_data_findings.rule_name',
            ])
            ->get()
            ->keyBy('name');
    }


    /*
    |--------------------------------------------------------------------------
    | Mask Sensitive Value
    |--------------------------------------------------------------------------
    */

    private function maskValue(
        mixed $value
    ): string {

        if ($value === null) {
            return 'NULL';
        }

        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        $length = mb_strlen($value);

        /*
        |--------------------------------------------------------------------------
        | Pendek
        |--------------------------------------------------------------------------
        */

        if ($length <= 4) {
            return str_repeat(
                '•',
                $length
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Tampilkan sebagian kecil
        |--------------------------------------------------------------------------
        */

        $visibleStart = min(
            2,
            $length
        );

        $visibleEnd = min(
            2,
            max(
                0,
                $length - $visibleStart
            )
        );

        $middleLength =
            $length
            - $visibleStart
            - $visibleEnd;

        return
            mb_substr(
                $value,
                0,
                $visibleStart
            )
            .
            str_repeat(
                '•',
                max(
                    3,
                    $middleLength
                )
            )
            .
            mb_substr(
                $value,
                $length - $visibleEnd
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Table Exists
    |--------------------------------------------------------------------------
    */

    private function tableExists(
        $db,
        string $driver,
        string $table
    ): bool {

        if (
            $driver === 'mysql'
            ||
            $driver === 'pgsql'
        ) {

            return $db
                ->getSchemaBuilder()
                ->hasTable($table);
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Columns
    |--------------------------------------------------------------------------
    */

    private function getColumns(
        $db,
        string $driver,
        string $table
    ): array {

        $schema =
            $db->getSchemaBuilder();

        $columns =
            $schema->getColumnListing(
                $table
            );

        $result = [];


        foreach ($columns as $column) {

            $result[] = [
                'name' =>
                    $column,

                'data_type' =>
                    null,

                'is_nullable' =>
                    null,

                'default' =>
                    null,

                'key' =>
                    null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | MySQL
        |--------------------------------------------------------------------------
        */

        if ($driver === 'mysql') {

            $database =
                $db->getDatabaseName();

            $information = $db->select(
                "
                SELECT
                    COLUMN_NAME,
                    DATA_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    COLUMN_KEY
                FROM information_schema.columns
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
                ",
                [
                    $database,
                    $table
                ]
            );


            $result = [];

            foreach (
                $information
                as $column
            ) {

                $result[] = [

                    'name' =>
                        $column->COLUMN_NAME,

                    'data_type' =>
                        $column->DATA_TYPE,

                    'is_nullable' =>
                        $column->IS_NULLABLE,

                    'default' =>
                        $column->COLUMN_DEFAULT,

                    'key' =>
                        $column->COLUMN_KEY,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PostgreSQL
        |--------------------------------------------------------------------------
        */

        if ($driver === 'pgsql') {

            $information = $db->select(
                "
                SELECT
                    column_name,
                    data_type,
                    is_nullable,
                    column_default
                FROM information_schema.columns
                WHERE table_name = ?
                ORDER BY ordinal_position
                ",
                [$table]
            );


            $result = [];

            foreach (
                $information
                as $column
            ) {

                $result[] = [

                    'name' =>
                        $column->column_name,

                    'data_type' =>
                        $column->data_type,

                    'is_nullable' =>
                        $column->is_nullable,

                    'default' =>
                        $column->column_default,

                    'key' =>
                        null,
                ];
            }
        }


        return $result;
    }
}