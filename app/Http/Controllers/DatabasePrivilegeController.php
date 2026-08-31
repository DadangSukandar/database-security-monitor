<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\DatabasePrivilege;
use App\Services\DatabaseConnectorService;
use Illuminate\Http\Request;
use Throwable;

class DatabasePrivilegeController extends Controller
{
    public function index(Request $request)
    {
        $query = DatabasePrivilege::query()
            ->with('databaseConnection');


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                $q->where(
                    'username',
                    'like',
                    '%' . $search . '%'
                );

                $q->orWhere(
                    'table_name',
                    'like',
                    '%' . $search . '%'
                );

                $q->orWhere(
                    'database_name',
                    'like',
                    '%' . $search . '%'
                );

                $q->orWhere(
                    'privilege',
                    'like',
                    '%' . $search . '%'
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Connection
        |--------------------------------------------------------------------------
        */

        if ($request->filled('connection')) {

            $query->where(
                'database_connection_id',
                $request->input('connection')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Privilege
        |--------------------------------------------------------------------------
        */

        if ($request->filled('privilege')) {

            $query->where(
                'privilege',
                strtoupper(
                    $request->input('privilege')
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Risk
        |--------------------------------------------------------------------------
        */

        if ($request->filled('risk')) {

            $query->where(
                'risk_level',
                strtoupper(
                    $request->input('risk')
                )
            );
        }


        $privileges = $query
            ->latest()
            ->paginate(30)
            ->withQueryString();


        $connections = DatabaseConnection::query()
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalPrivileges =
            DatabasePrivilege::count();

        $highRisk =
            DatabasePrivilege::where(
                'risk_level',
                'HIGH'
            )->count();

        $mediumRisk =
            DatabasePrivilege::where(
                'risk_level',
                'MEDIUM'
            )->count();

        $grantable =
            DatabasePrivilege::where(
                'is_grantable',
                true
            )->count();


        return view(
            'database-privileges.index',
            compact(
                'privileges',
                'connections',
                'totalPrivileges',
                'highRisk',
                'mediumRisk',
                'grantable'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Scan
    |--------------------------------------------------------------------------
    */

    public function scan(
        DatabaseConnection $databaseConnection,
        DatabaseConnectorService $connector
    ) {
        try {

            $db = $connector->connect(
                $databaseConnection
            );


            /*
            |--------------------------------------------------------------------------
            | Delete previous scan
            |--------------------------------------------------------------------------
            */

            DatabasePrivilege::where(
                'database_connection_id',
                $databaseConnection->id
            )->delete();


            /*
            |--------------------------------------------------------------------------
            | MySQL
            |--------------------------------------------------------------------------
            */

            if (
                $databaseConnection->driver === 'mysql'
            ) {

                $this->scanMySql(
                    $databaseConnection,
                    $db
                );
            }


            /*
            |--------------------------------------------------------------------------
            | PostgreSQL
            |--------------------------------------------------------------------------
            */

            elseif (
                $databaseConnection->driver === 'pgsql'
            ) {

                $this->scanPostgreSql(
                    $databaseConnection,
                    $db
                );
            }


            else {

                throw new \RuntimeException(
                    'Driver tidak didukung.'
                );
            }


            return back()->with(
                'success',
                'Database privileges berhasil di-scan.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'scan' =>
                    'Privilege scan gagal: ' .
                    $e->getMessage()
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MySQL
    |--------------------------------------------------------------------------
    */

    private function scanMySql(
        DatabaseConnection $connection,
        $db
    ): void {

        /*
        |--------------------------------------------------------------------------
        | 1. GLOBAL PRIVILEGES
        |--------------------------------------------------------------------------
        */

        $users = $db->select("
            SELECT
                User,
                Host,
                Select_priv,
                Insert_priv,
                Update_priv,
                Delete_priv,
                Create_priv,
                Drop_priv,
                Alter_priv,
                Index_priv,
                Create_view_priv,
                Show_view_priv,
                Create_routine_priv,
                Alter_routine_priv,
                Execute_priv,
                Event_priv,
                Trigger_priv,
                Grant_priv
            FROM mysql.user
        ");


        foreach ($users as $user) {

            $privileges = [

                'SELECT' =>
                    $user->Select_priv,

                'INSERT' =>
                    $user->Insert_priv,

                'UPDATE' =>
                    $user->Update_priv,

                'DELETE' =>
                    $user->Delete_priv,

                'CREATE' =>
                    $user->Create_priv,

                'DROP' =>
                    $user->Drop_priv,

                'ALTER' =>
                    $user->Alter_priv,

                'INDEX' =>
                    $user->Index_priv,

                'CREATE VIEW' =>
                    $user->Create_view_priv,

                'SHOW VIEW' =>
                    $user->Show_view_priv,

                'CREATE ROUTINE' =>
                    $user->Create_routine_priv,

                'ALTER ROUTINE' =>
                    $user->Alter_routine_priv,

                'EXECUTE' =>
                    $user->Execute_priv,

                'EVENT' =>
                    $user->Event_priv,

                'TRIGGER' =>
                    $user->Trigger_priv,

                'GRANT' =>
                    $user->Grant_priv,
            ];


            foreach ($privileges as $privilege => $enabled) {

                if (strtoupper((string) $enabled) !== 'Y') {
                    continue;
                }


                $risk = $this->calculateRisk(
                    $privilege,
                    'NO'
                );


                DatabasePrivilege::create([

                    'database_connection_id' =>
                        $connection->id,

                    'username' =>
                        $user->User,

                    'host' =>
                        $user->Host,

                    'database_name' =>
                        '*',

                    'schema_name' =>
                        null,

                    'table_name' =>
                        '*',

                    'privilege' =>
                        $privilege,

                    'is_grantable' =>
                        $privilege === 'GRANT',

                    'risk_level' =>
                        $risk['level'],

                    'risk_reason' =>
                        'Global MySQL privilege. ' .
                        $risk['reason'],

                    'last_scanned_at' =>
                        now(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. DATABASE PRIVILEGES
        |--------------------------------------------------------------------------
        */

        $databasePrivileges = $db->select("
            SELECT
                User,
                Host,
                Db,
                Select_priv,
                Insert_priv,
                Update_priv,
                Delete_priv,
                Create_priv,
                Drop_priv,
                Alter_priv,
                Grant_priv,
                Index_priv,
                Create_view_priv,
                Show_view_priv,
                Create_routine_priv,
                Alter_routine_priv,
                Execute_priv,
                Event_priv,
                Trigger_priv
            FROM mysql.db
        ");


        foreach ($databasePrivileges as $row) {

            $privileges = [

                'SELECT' =>
                    $row->Select_priv,

                'INSERT' =>
                    $row->Insert_priv,

                'UPDATE' =>
                    $row->Update_priv,

                'DELETE' =>
                    $row->Delete_priv,

                'CREATE' =>
                    $row->Create_priv,

                'DROP' =>
                    $row->Drop_priv,

                'ALTER' =>
                    $row->Alter_priv,

                'GRANT' =>
                    $row->Grant_priv,

                'INDEX' =>
                    $row->Index_priv,

                'CREATE VIEW' =>
                    $row->Create_view_priv,

                'SHOW VIEW' =>
                    $row->Show_view_priv,

                'CREATE ROUTINE' =>
                    $row->Create_routine_priv,

                'ALTER ROUTINE' =>
                    $row->Alter_routine_priv,

                'EXECUTE' =>
                    $row->Execute_priv,

                'EVENT' =>
                    $row->Event_priv,

                'TRIGGER' =>
                    $row->Trigger_priv,
            ];


            foreach ($privileges as $privilege => $enabled) {

                if (strtoupper((string) $enabled) !== 'Y') {
                    continue;
                }


                $risk = $this->calculateRisk(
                    $privilege,
                    $row->Grant_priv
                );


                DatabasePrivilege::create([

                    'database_connection_id' =>
                        $connection->id,

                    'username' =>
                        $row->User,

                    'host' =>
                        $row->Host,

                    'database_name' =>
                        $row->Db,

                    'schema_name' =>
                        null,

                    'table_name' =>
                        '*',

                    'privilege' =>
                        $privilege,

                    'is_grantable' =>
                        strtoupper(
                            (string) $row->Grant_priv
                        ) === 'Y',

                    'risk_level' =>
                        $risk['level'],

                    'risk_reason' =>
                        'Database-level privilege. ' .
                        $risk['reason'],

                    'last_scanned_at' =>
                        now(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 3. TABLE PRIVILEGES
        |--------------------------------------------------------------------------
        */

        $tablePrivileges = $db->select("
            SELECT
                User,
                Host,
                Db,
                Table_name,
                Table_priv,
                Grantor
            FROM mysql.tables_priv
        ");


        foreach ($tablePrivileges as $row) {

            $privileges = preg_split(
                '/[,;]/',
                (string) $row->Table_priv
            );


            foreach ($privileges as $privilege) {

                $privilege = strtoupper(
                    trim($privilege)
                );


                if ($privilege === '') {
                    continue;
                }


                $isGrantable =
                    str_contains(
                        strtoupper(
                            (string) $row->Table_priv
                        ),
                        'GRANT'
                    );


                $risk = $this->calculateRisk(
                    $privilege,
                    $isGrantable ? 'YES' : 'NO'
                );


                DatabasePrivilege::create([

                    'database_connection_id' =>
                        $connection->id,

                    'username' =>
                        $row->User,

                    'host' =>
                        $row->Host,

                    'database_name' =>
                        $row->Db,

                    'schema_name' =>
                        null,

                    'table_name' =>
                        $row->Table_name,

                    'privilege' =>
                        $privilege,

                    'is_grantable' =>
                        $isGrantable,

                    'risk_level' =>
                        $risk['level'],

                    'risk_reason' =>
                        'Table-level privilege. ' .
                        $risk['reason'],

                    'last_scanned_at' =>
                        now(),
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PostgreSQL
    |--------------------------------------------------------------------------
    */

    private function scanPostgreSql(
        DatabaseConnection $connection,
        $db
    ): void {

        $rows = $db->select(
            "
            SELECT
                grantee,
                table_catalog,
                table_schema,
                table_name,
                privilege_type,
                is_grantable
            FROM information_schema.role_table_grants
            WHERE table_schema NOT IN
                (
                    'pg_catalog',
                    'information_schema'
                )
            ORDER BY
                grantee,
                table_schema,
                table_name,
                privilege_type
            "
        );


        foreach ($rows as $row) {

            $risk =
                $this->calculateRisk(
                    $row->privilege_type,
                    $row->is_grantable
                );


            DatabasePrivilege::create([

                'database_connection_id' =>
                    $connection->id,

                'username' =>
                    $row->grantee,

                'host' =>
                    null,

                'database_name' =>
                    $row->table_catalog,

                'schema_name' =>
                    $row->table_schema,

                'table_name' =>
                    $row->table_name,

                'privilege' =>
                    strtoupper(
                        $row->privilege_type
                    ),

                'is_grantable' =>
                    strtoupper(
                        $row->is_grantable
                    ) === 'YES',

                'risk_level' =>
                    $risk['level'],

                'risk_reason' =>
                    $risk['reason'],

                'last_scanned_at' =>
                    now(),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Risk Calculation
    |--------------------------------------------------------------------------
    */

    private function calculateRisk(
        string $privilege,
        string $grantable
    ): array {

        $privilege = strtoupper(
            trim($privilege)
        );


        $grantable =
            strtoupper(
                trim($grantable)
            ) === 'YES';


        /*
        |--------------------------------------------------------------------------
        | CRITICAL / HIGH
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $privilege,
                [
                    'GRANT',
                    'DROP',
                    'ALTER',
                ],
                true
            )
        ) {

            return [
                'level' => 'HIGH',

                'reason' =>
                    'Privilege dapat mengubah struktur atau hak akses database.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | GRANTABLE
        |--------------------------------------------------------------------------
        */

        if ($grantable) {

            return [
                'level' => 'HIGH',

                'reason' =>
                    'User dapat memberikan privilege kepada user lain.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | WRITE
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $privilege,
                [
                    'INSERT',
                    'UPDATE',
                    'DELETE',
                    'TRUNCATE',
                    'CREATE',
                ],
                true
            )
        ) {

            return [
                'level' => 'MEDIUM',

                'reason' =>
                    'User memiliki kemampuan melakukan perubahan terhadap database.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | READ
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $privilege,
                [
                    'SELECT',
                    'SHOW VIEW',
                ],
                true
            )
        ) {

            return [
                'level' => 'LOW',

                'reason' =>
                    'User memiliki akses membaca data.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        return [
            'level' => 'LOW',

            'reason' =>
                'Privilege database standar.',
        ];
    }
}