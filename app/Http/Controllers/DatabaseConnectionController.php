<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredColumn;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use App\Services\DatabaseConnectorService;
use Illuminate\Http\Request;
use Throwable;

class DatabaseConnectionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $connections = DatabaseConnection::latest()
            ->paginate(10);

        return view(
            'database-connections.index',
            compact('connections')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view(
            'database-connections.create'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        DatabaseConnectorService $connector
    ) {
        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'driver' => [
                'required',
                'in:mysql,pgsql',
            ],

            'host' => [
                'required',
                'string',
                'max:255',
            ],

            'port' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'database' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'nullable',
                'string',
            ],

            'schema' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN CONNECTION
        |--------------------------------------------------------------------------
        */

        $connection = DatabaseConnection::create(
            $validated
        );

        try {

            /*
            |--------------------------------------------------------------------------
            | TEST CONNECTION
            |--------------------------------------------------------------------------
            */

            $connector->test(
                $connection
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE LAST CONNECTED
            |--------------------------------------------------------------------------
            */

            $connection->update([
                'last_connected_at' => now(),
            ]);

            return redirect()
                ->route(
                    'database-connections.show',
                    $connection
                )
                ->with(
                    'success',
                    'Koneksi berhasil dibuat dan berhasil terhubung.'
                );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | JIKA GAGAL, HAPUS CONNECTION
            |--------------------------------------------------------------------------
            */

            $connection->delete();

            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'connection' => 'Koneksi gagal: '.
                        $this->safeExceptionDetail($e),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        DatabaseConnection $databaseConnection
    ) {

        $databaseConnection->load([
            'discoveredDatabases.tables.columns',
        ]);

        return view(
            'database-connections.show',
            compact('databaseConnection')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TEST CONNECTION
    |--------------------------------------------------------------------------
    */

    public function test(
        DatabaseConnection $databaseConnection,
        DatabaseConnectorService $connector
    ) {

        try {

            $connector->test(
                $databaseConnection
            );

            $databaseConnection->update([
                'last_connected_at' => now(),
            ]);

            return back()->with(
                'success',
                'Database connection berhasil.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'connection' => 'Connection failed: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SCAN DATABASE
    |--------------------------------------------------------------------------
    */

    public function scan(
        DatabaseConnection $databaseConnection,
        DatabaseConnectorService $connector
    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | CONNECT KE DATABASE TARGET
            |--------------------------------------------------------------------------
            */

            $db = $connector->connect(
                $databaseConnection
            );

            /*
            |--------------------------------------------------------------------------
            | HAPUS HASIL SCAN LAMA
            |--------------------------------------------------------------------------
            |
            | Supaya information_schema yang sebelumnya sudah tersimpan
            | tidak muncul lagi.
            |
            */

            $this->clearPreviousScan(
                $databaseConnection
            );

            /*
            |--------------------------------------------------------------------------
            | SCAN DATABASE YANG DIPILIH
            |--------------------------------------------------------------------------
            */

            $this->scanSelectedDatabase(
                $databaseConnection,
                $db
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS CONNECTION
            |--------------------------------------------------------------------------
            */

            $databaseConnection->update([
                'last_scanned_at' => now(),
                'last_connected_at' => now(),
            ]);

            return back()->with(
                'success',
                'Database berhasil di-scan.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'scan' => 'Scan gagal: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAR PREVIOUS SCAN
    |--------------------------------------------------------------------------
    */

    private function clearPreviousScan(
        DatabaseConnection $connection
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Ambil database discovery lama
        |--------------------------------------------------------------------------
        */

        $databases = DiscoveredDatabase::where(
            'database_connection_id',
            $connection->id
        )->get();

        foreach ($databases as $database) {

            /*
            |--------------------------------------------------------------------------
            | Ambil table
            |--------------------------------------------------------------------------
            */

            $tables = DiscoveredTable::where(
                'discovered_database_id',
                $database->id
            )->get();

            foreach ($tables as $table) {

                /*
                |--------------------------------------------------------------------------
                | Hapus columns
                |--------------------------------------------------------------------------
                */

                DiscoveredColumn::where(
                    'discovered_table_id',
                    $table->id
                )->delete();

                /*
                |--------------------------------------------------------------------------
                | Hapus table
                |--------------------------------------------------------------------------
                */

                $table->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | Hapus database
            |--------------------------------------------------------------------------
            */

            $database->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SCAN SELECTED DATABASE
    |--------------------------------------------------------------------------
    */

    private function scanSelectedDatabase(
        DatabaseConnection $connection,
        $db
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Nama database dari form connection
        |--------------------------------------------------------------------------
        */

        $databaseName = $connection->database;

        /*
        |--------------------------------------------------------------------------
        | Simpan database discovery
        |--------------------------------------------------------------------------
        */

        $database = DiscoveredDatabase::updateOrCreate(

            [
                'database_connection_id' => $connection->id,

                'name' => $databaseName,
            ],

            [
                'engine' => $connection->driver,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Scan tabel
        |--------------------------------------------------------------------------
        */

        $this->scanTables(
            $database,
            $connection,
            $db,
            $databaseName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCAN TABLES
    |--------------------------------------------------------------------------
    */

    private function scanTables(
        DiscoveredDatabase $database,
        DatabaseConnection $connection,
        $db,
        string $databaseName
    ): void {

        /*
        |--------------------------------------------------------------------------
        | MYSQL
        |--------------------------------------------------------------------------
        */

        if ($connection->driver === 'mysql') {

            $tables = $db->select(
                'SELECT
                    TABLE_SCHEMA,
                    TABLE_NAME,
                    TABLE_TYPE
                 FROM information_schema.tables
                 WHERE TABLE_SCHEMA = ?
                 ORDER BY TABLE_NAME',
                [
                    $databaseName,
                ]
            );

            foreach ($tables as $table) {

                $this->saveTable(
                    $database,
                    $connection,
                    $db,
                    $table->TABLE_SCHEMA,
                    $table->TABLE_NAME,
                    $table->TABLE_TYPE
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | POSTGRESQL
        |--------------------------------------------------------------------------
        */

        if ($connection->driver === 'pgsql') {

            $schema = $connection->schema
                ?: 'public';

            $tables = $db->select(
                'SELECT
                    table_schema,
                    table_name,
                    table_type
                 FROM information_schema.tables
                 WHERE table_schema = ?
                 ORDER BY table_name',
                [
                    $schema,
                ]
            );

            foreach ($tables as $table) {

                $this->saveTable(
                    $database,
                    $connection,
                    $db,
                    $table->table_schema,
                    $table->table_name,
                    $table->table_type
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE TABLE + COLUMNS
    |--------------------------------------------------------------------------
    */

    private function saveTable(
        DiscoveredDatabase $database,
        DatabaseConnection $connection,
        $db,
        string $schema,
        string $name,
        string $type
    ): void {

        /*
        |--------------------------------------------------------------------------
        | SIMPAN TABLE
        |--------------------------------------------------------------------------
        */

        $table = DiscoveredTable::updateOrCreate(

            [
                'discovered_database_id' => $database->id,

                'schema_name' => $schema,

                'name' => $name,
            ],

            [
                'type' => $type,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | HAPUS COLUMN LAMA
        |--------------------------------------------------------------------------
        */

        DiscoveredColumn::where(
            'discovered_table_id',
            $table->id
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | MYSQL COLUMNS
        |--------------------------------------------------------------------------
        */

        if ($connection->driver === 'mysql') {

            $columns = $db->select(
                'SELECT
                    COLUMN_NAME,
                    DATA_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    COLUMN_KEY,
                    ORDINAL_POSITION
                 FROM information_schema.columns
                 WHERE TABLE_SCHEMA = ?
                 AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION',
                [
                    $schema,
                    $name,
                ]
            );

            foreach ($columns as $column) {

                DiscoveredColumn::create([

                    'discovered_table_id' => $table->id,

                    'name' => $column->COLUMN_NAME,

                    'data_type' => $column->DATA_TYPE,

                    'is_nullable' => $column->IS_NULLABLE,

                    'default' => $column->COLUMN_DEFAULT,

                    'is_primary' => strtoupper(
                        $column->COLUMN_KEY ?? ''
                    ) === 'PRI',
                ]);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | POSTGRESQL COLUMNS
        |--------------------------------------------------------------------------
        */

        if ($connection->driver === 'pgsql') {

            $columns = $db->select(
                "SELECT
                    c.column_name,
                    c.data_type,
                    c.is_nullable,
                    c.column_default,
                    c.ordinal_position,
                    CASE
                        WHEN EXISTS (
                            SELECT 1
                            FROM information_schema.table_constraints tc
                            INNER JOIN information_schema.key_column_usage kcu
                                ON tc.constraint_name = kcu.constraint_name
                                AND tc.table_schema = kcu.table_schema
                            WHERE tc.constraint_type = 'PRIMARY KEY'
                            AND tc.table_schema = c.table_schema
                            AND tc.table_name = c.table_name
                            AND kcu.column_name = c.column_name
                        )
                        THEN true
                        ELSE false
                    END AS is_primary
                 FROM information_schema.columns c
                 WHERE c.table_schema = ?
                 AND c.table_name = ?
                 ORDER BY c.ordinal_position",
                [
                    $schema,
                    $name,
                ]
            );

            foreach ($columns as $column) {

                DiscoveredColumn::create([

                    'discovered_table_id' => $table->id,

                    'name' => $column->column_name,

                    'data_type' => $column->data_type,

                    'is_nullable' => $column->is_nullable,

                    'default' => $column->column_default,

                    'is_primary' => (bool) $column->is_primary,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        DatabaseConnection $databaseConnection
    ) {

        /*
        |--------------------------------------------------------------------------
        | Hapus metadata discovery
        |--------------------------------------------------------------------------
        */

        $this->clearPreviousScan(
            $databaseConnection
        );

        /*
        |--------------------------------------------------------------------------
        | Hapus connection
        |--------------------------------------------------------------------------
        */

        $databaseConnection->delete();

        return redirect()
            ->route(
                'database-connections.index'
            )
            ->with(
                'success',
                'Connection berhasil dihapus.'
            );
    }
}
