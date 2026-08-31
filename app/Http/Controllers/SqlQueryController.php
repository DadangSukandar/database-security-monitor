<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\DatabaseActivityLogger;
use App\Services\DatabaseConnectorService;
use Illuminate\Http\Request;
use Throwable;

class SqlQueryController extends Controller
{
    public function index()
    {
        $connections = DatabaseConnection::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'sql-query.index',
            compact('connections')
        );
    }


    public function execute(
        Request $request,
        DatabaseConnectorService $connector,
        DatabaseActivityLogger $activityLogger
    ) {
        $request->validate([
            'database_connection_id' => [
                'required',
                'integer',
                'exists:database_connections,id',
            ],

            'query' => [
                'required',
                'string',
                'max:50000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Ambil Connection
        |--------------------------------------------------------------------------
        */

        $databaseConnection =
            DatabaseConnection::findOrFail(
                $request->database_connection_id
            );


        /*
        |--------------------------------------------------------------------------
        | Bersihkan Query
        |--------------------------------------------------------------------------
        */

        $sql = trim(
            $request->input('query')
        );


        /*
        |--------------------------------------------------------------------------
        | Pastikan Query Read-Only
        |--------------------------------------------------------------------------
        */

        if (!$this->isReadOnlyQuery($sql)) {

            return back()
                ->withInput()
                ->withErrors([
                    'query' =>
                        'Query ditolak. SQL Console saat ini hanya mengizinkan SELECT, SHOW, DESCRIBE, DESC, dan EXPLAIN.'
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Connect Database
        |--------------------------------------------------------------------------
        */

        try {

            $db = $connector->connect(
                $databaseConnection
            );

        } catch (Throwable $e) {

            return back()
                ->withInput()
                ->withErrors([
                    'query' =>
                        'Gagal koneksi database: ' .
                        $e->getMessage()
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Execute Query
        |--------------------------------------------------------------------------
        */

        $startTime = microtime(true);


        try {

            $rows = $db->select(
                $sql
            );


            $executionTimeMs =
                (int) round(
                    (
                        microtime(true)
                        - $startTime
                    ) * 1000
                );


            /*
            |--------------------------------------------------------------------------
            | Convert Result
            |--------------------------------------------------------------------------
            */

            $rows = collect($rows)
                ->map(function ($row) {

                    return (array) $row;

                })
                ->values();


            /*
            |--------------------------------------------------------------------------
            | Ambil Column
            |--------------------------------------------------------------------------
            */

            $columns = [];

            if ($rows->count() > 0) {

                $columns = array_keys(
                    $rows->first()
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Log Activity
            |--------------------------------------------------------------------------
            */

            $activityLogger->success(
                $databaseConnection,
                $sql,
                'SELECT',
                null,
                $executionTimeMs
            );


            return view(
                'sql-query.index',
                [
                    'connections' =>
                        DatabaseConnection::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get(),

                    'selectedConnection' =>
                        $databaseConnection,

                    'query' =>
                        $sql,

                    'rows' =>
                        $rows,

                    'columns' =>
                        $columns,

                    'executionTimeMs' =>
                        $executionTimeMs,

                    'resultCount' =>
                        $rows->count(),
                ]
            );


        } catch (Throwable $e) {

            $executionTimeMs =
                (int) round(
                    (
                        microtime(true)
                        - $startTime
                    ) * 1000
                );


            /*
            |--------------------------------------------------------------------------
            | Log Failed Query
            |--------------------------------------------------------------------------
            */

            $activityLogger->failed(
                $databaseConnection,
                $sql,
                'SELECT',
                null,
                $e,
                $executionTimeMs
            );


            return view(
                'sql-query.index',
                [
                    'connections' =>
                        DatabaseConnection::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get(),

                    'selectedConnection' =>
                        $databaseConnection,

                    'query' =>
                        $sql,

                    'rows' =>
                        collect(),

                    'columns' =>
                        [],

                    'executionTimeMs' =>
                        $executionTimeMs,

                    'resultCount' =>
                        0,

                    'error' =>
                        $e->getMessage(),
                ]
            );
        }
    }


    private function isReadOnlyQuery(
        string $sql
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Remove whitespace
        |--------------------------------------------------------------------------
        */

        $sql = trim($sql);


        /*
        |--------------------------------------------------------------------------
        | Remove SQL comments
        |--------------------------------------------------------------------------
        */

        $sql = preg_replace(
            '/--.*$/m',
            '',
            $sql
        );

        $sql = preg_replace(
            '/\/\*.*?\*\//s',
            '',
            $sql
        );


        $sql = trim($sql);


        /*
        |--------------------------------------------------------------------------
        | Ambil keyword pertama
        |--------------------------------------------------------------------------
        */

        if (
            !preg_match(
                '/^([a-zA-Z]+)/',
                $sql,
                $matches
            )
        ) {
            return false;
        }


        $command = strtoupper(
            $matches[1]
        );


        return in_array(
            $command,
            [
                'SELECT',
                'SHOW',
                'DESCRIBE',
                'DESC',
                'EXPLAIN',
            ],
            true
        );
    }
}