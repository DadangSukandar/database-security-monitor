<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\DatabaseUser;
use App\Services\DatabaseConnectorService;
use Illuminate\Http\Request;
use Throwable;

class DatabaseUserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ) {
        $query = DatabaseUser::query()
            ->with(
                'databaseConnection'
            );

        /*
        |--------------------------------------------------------------------------
        | Connection Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'connection'
            )
        ) {

            $query->where(
                'database_connection_id',
                $request->connection
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Risk Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'risk'
            )
        ) {

            $query->where(
                'risk_level',
                strtoupper(
                    $request->risk
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'search'
            )
        ) {

            $search =
                $request->search;

            $query->where(function ($q) use (
                $search
            ) {

                $q->where(
                    'username',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'host',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $users = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $connections =
            DatabaseConnection::query()
                ->orderBy('name')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalUsers =
            DatabaseUser::count();

        $highRiskUsers =
            DatabaseUser::where(
                'risk_level',
                'HIGH'
            )->count();

        $mediumRiskUsers =
            DatabaseUser::where(
                'risk_level',
                'MEDIUM'
            )->count();

        $superUsers =
            DatabaseUser::where(
                'is_superuser',
                true
            )->count();

        return view(
            'database-users.index',
            compact(
                'users',
                'connections',
                'totalUsers',
                'highRiskUsers',
                'mediumRiskUsers',
                'superUsers'
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

            $db =
                $connector->connect(
                    $databaseConnection
                );

            /*
            |--------------------------------------------------------------------------
            | Hapus hasil scan lama
            |--------------------------------------------------------------------------
            */

            DatabaseUser::where(
                'database_connection_id',
                $databaseConnection->id
            )->delete();

            /*
            |--------------------------------------------------------------------------
            | MySQL
            |--------------------------------------------------------------------------
            */

            if (
                $databaseConnection->driver
                === 'mysql'
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
                $databaseConnection->driver
                === 'pgsql'
            ) {

                $this->scanPostgreSql(
                    $databaseConnection,
                    $db
                );
            } else {

                throw new \RuntimeException(
                    'Driver tidak didukung.'
                );
            }

            return back()->with(
                'success',
                'Database users berhasil di-scan.'
            );

        } catch (Throwable $e) {

            return back()
                ->withErrors([
                    'scan' => 'Scan user gagal: '.
                        $this->safeExceptionDetail($e),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MySQL User Scanner
    |--------------------------------------------------------------------------
    */

    private function scanMySql(
        DatabaseConnection $connection,
        $db
    ): void {

        $users = $db->select(
            '
            SELECT
                User,
                Host,
                plugin,
                account_locked,
                Super_priv,
                Create_user_priv,
                Create_priv,
                Grant_priv,
                Repl_slave_priv,
                Select_priv,
                Insert_priv,
                Update_priv,
                Delete_priv
            FROM mysql.user
            ORDER BY User, Host
            '
        );

        foreach (
            $users as $user
        ) {

            $isSuper =
                strtoupper(
                    (string) $user->Super_priv
                ) === 'Y';

            $canCreateRole =
                strtoupper(
                    (string) $user->Create_user_priv
                ) === 'Y';

            $canCreateDatabase =
                strtoupper(
                    (string) $user->Create_priv
                ) === 'Y';

            $canGrant =
                strtoupper(
                    (string) $user->Grant_priv
                ) === 'Y';

            $isReplication =
                strtoupper(
                    (string) $user->Repl_slave_priv
                ) === 'Y';

            $isLocked =
                strtoupper(
                    (string) $user->account_locked
                ) === 'Y';

            $risk = $this->calculateRisk(
                isSuper: $isSuper,
                isLocked: $isLocked,
                canGrant: $canGrant,
                canCreateRole: $canCreateRole,
                canCreateDatabase: $canCreateDatabase,
                isReplication: $isReplication,
                canLogin: true,
                bypassRls: false
            );

            DatabaseUser::create([

                'database_connection_id' => $connection->id,

                'username' => $user->User,

                'host' => $user->Host,

                'authentication_plugin' => $user->plugin,

                'can_login' => true,

                'is_superuser' => $isSuper,

                'is_locked' => $isLocked,

                'can_create_database' => $canCreateDatabase,

                'can_create_role' => $canCreateRole,

                'can_grant' => $canGrant,

                'is_replication' => $isReplication,

                'bypass_rls' => false,

                'risk_level' => $risk['level'],

                'risk_reasons' => implode(
                    "\n",
                    $risk['reasons']
                ),

                'last_scanned_at' => now(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL User Scanner
    |--------------------------------------------------------------------------
    */

    private function scanPostgreSql(
        DatabaseConnection $connection,
        $db
    ): void {

        $users = $db->select(
            '
            SELECT
                rolname,
                rolsuper,
                rolcreaterole,
                rolcreatedb,
                rolcanlogin,
                rolreplication,
                rolbypassrls
            FROM pg_roles
            ORDER BY rolname
            '
        );

        foreach (
            $users as $user
        ) {

            $isSuper =
                (bool) $user->rolsuper;

            $canCreateRole =
                (bool) $user->rolcreaterole;

            $canCreateDatabase =
                (bool) $user->rolcreatedb;

            $canLogin =
                (bool) $user->rolcanlogin;

            $isReplication =
                (bool) $user->rolreplication;

            $bypassRls =
                (bool) $user->rolbypassrls;

            $risk = $this->calculateRisk(
                isSuper: $isSuper,
                isLocked: false,
                canGrant: $canCreateRole,
                canCreateRole: $canCreateRole,
                canCreateDatabase: $canCreateDatabase,
                isReplication: $isReplication,
                canLogin: $canLogin,
                bypassRls: $bypassRls
            );

            DatabaseUser::create([

                'database_connection_id' => $connection->id,

                'username' => $user->rolname,

                'host' => null,

                'authentication_plugin' => null,

                'can_login' => $canLogin,

                'is_superuser' => $isSuper,

                'is_locked' => false,

                'can_create_database' => $canCreateDatabase,

                'can_create_role' => $canCreateRole,

                'can_grant' => $canCreateRole,

                'is_replication' => $isReplication,

                'bypass_rls' => $bypassRls,

                'risk_level' => $risk['level'],

                'risk_reasons' => implode(
                    "\n",
                    $risk['reasons']
                ),

                'last_scanned_at' => now(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Risk Calculation
    |--------------------------------------------------------------------------
    */

    private function calculateRisk(
        bool $isSuper,
        bool $isLocked,
        bool $canGrant,
        bool $canCreateRole,
        bool $canCreateDatabase,
        bool $isReplication,
        bool $canLogin,
        bool $bypassRls
    ): array {

        $reasons = [];

        $score = 0;

        /*
        |--------------------------------------------------------------------------
        | Superuser
        |--------------------------------------------------------------------------
        */

        if ($isSuper) {

            $score += 70;

            $reasons[] =
                'Superuser / administrative privileges';
        }

        /*
        |--------------------------------------------------------------------------
        | Grant privilege
        |--------------------------------------------------------------------------
        */

        if ($canGrant) {

            $score += 25;

            $reasons[] =
                'Can grant privileges';
        }

        /*
        |--------------------------------------------------------------------------
        | Create role
        |--------------------------------------------------------------------------
        */

        if ($canCreateRole) {

            $score += 20;

            $reasons[] =
                'Can create database roles';
        }

        /*
        |--------------------------------------------------------------------------
        | Create database
        |--------------------------------------------------------------------------
        */

        if ($canCreateDatabase) {

            $score += 10;

            $reasons[] =
                'Can create databases';
        }

        /*
        |--------------------------------------------------------------------------
        | Replication
        |--------------------------------------------------------------------------
        */

        if ($isReplication) {

            $score += 15;

            $reasons[] =
                'Replication privilege';
        }

        /*
        |--------------------------------------------------------------------------
        | Bypass RLS
        |--------------------------------------------------------------------------
        */

        if ($bypassRls) {

            $score += 30;

            $reasons[] =
                'Can bypass row level security';
        }

        /*
        |--------------------------------------------------------------------------
        | Login
        |--------------------------------------------------------------------------
        */

        if (
            $canLogin
            &&
            $isSuper
        ) {

            $score += 10;

            $reasons[] =
                'Privileged account can login';
        }

        /*
        |--------------------------------------------------------------------------
        | Locked
        |--------------------------------------------------------------------------
        */

        if ($isLocked) {

            $reasons[] =
                'Account is locked';
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Risk
        |--------------------------------------------------------------------------
        */

        if ($score >= 70) {

            $level = 'HIGH';

        } elseif ($score >= 30) {

            $level = 'MEDIUM';

        } else {

            $level = 'LOW';
        }

        return [
            'level' => $level,

            'reasons' => $reasons,
        ];
    }
}
