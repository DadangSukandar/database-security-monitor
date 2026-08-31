<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use Illuminate\Support\Facades\DB;
use Throwable;

class SecurityAuditScanner
{
    public function __construct(
        protected DatabaseConnectorService $connector
    ) {
    }

    /**
     * Jalankan security audit terhadap database.
     */
    public function scan(DatabaseConnection $connection): array
    {
        $db = $this->connector->connect($connection);

        $result = [
            'total' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Hapus finding OPEN lama untuk connection ini
        |--------------------------------------------------------------------------
        */

        SecurityFinding::where(
            'database_connection_id',
            $connection->id
        )
            ->where('status', 'OPEN')
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Scan berdasarkan driver
        |--------------------------------------------------------------------------
        */

        if ($connection->driver === 'mysql') {

            $this->scanMySql(
                $db,
                $connection,
                $result
            );

        } elseif ($connection->driver === 'pgsql') {

            $this->scanPostgreSql(
                $db,
                $connection,
                $result
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Hitung ulang total
        |--------------------------------------------------------------------------
        */

        $result['total'] =
            $result['critical'] +
            $result['high'] +
            $result['medium'] +
            $result['low'];

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | MySQL
    |--------------------------------------------------------------------------
    */

    protected function scanMySql(
        $db,
        DatabaseConnection $connection,
        array &$result
    ): void {

        /*
        |--------------------------------------------------------------------------
        | 1. Scan database users
        |--------------------------------------------------------------------------
        */

        try {

            $users = $db->select("
                SELECT
                    User,
                    Host,
                    account_locked,
                    plugin
                FROM mysql.user
            ");

            foreach ($users as $user) {

                $username = $user->User;
                $host = $user->Host;

                /*
                |--------------------------------------------------------------------------
                | Remote account
                |--------------------------------------------------------------------------
                */

                if ($host === '%') {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'REMOTE_ACCOUNT',
                        category: 'ACCESS_CONTROL',
                        severity: 'MEDIUM',
                        title: 'Database account dapat login dari host mana saja',
                        description:
                            "User {$username} menggunakan host '%' sehingga dapat mencoba login dari berbagai host.",
                        objectType: 'USER',
                        objectName: $username,
                        username: $username,
                        recommendation:
                            "Batasi Host user menjadi alamat atau jaringan yang memang diperlukan.",
                        result: $result
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Locked account
                |--------------------------------------------------------------------------
                */

                if (
                    isset($user->account_locked) &&
                    strtoupper((string) $user->account_locked) === 'Y'
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'LOCKED_ACCOUNT',
                        category: 'ACCOUNT_SECURITY',
                        severity: 'LOW',
                        title: 'Database account dalam kondisi locked',
                        description:
                            "User {$username} dalam kondisi locked.",
                        objectType: 'USER',
                        objectName: $username,
                        username: $username,
                        recommendation:
                            'Hapus account yang tidak diperlukan atau pastikan status account sesuai kebutuhan.',
                        result: $result
                    );
                }
            }

        } catch (Throwable) {
            /*
             * User scanner tidak boleh menghentikan seluruh audit.
             */
        }


        /*
        |--------------------------------------------------------------------------
        | 2. Scan privileges
        |--------------------------------------------------------------------------
        */

        try {

            $privileges = $db->select("
                SELECT
                    GRANTEE,
                    TABLE_SCHEMA,
                    TABLE_NAME,
                    PRIVILEGE_TYPE,
                    IS_GRANTABLE
                FROM information_schema.TABLE_PRIVILEGES
            ");

            foreach ($privileges as $privilege) {

                $grantee = trim(
                    (string) $privilege->GRANTEE,
                    "'"
                );

                $privilegeName =
                    strtoupper(
                        (string) $privilege->PRIVILEGE_TYPE
                    );

                $grantable =
                    strtoupper(
                        (string) $privilege->IS_GRANTABLE
                    );


                /*
                |--------------------------------------------------------------------------
                | GRANT OPTION
                |--------------------------------------------------------------------------
                */

                if ($grantable === 'YES') {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $privilege->TABLE_SCHEMA,
                        findingType: 'GRANTABLE_PRIVILEGE',
                        category: 'PRIVILEGE',
                        severity: 'HIGH',
                        title: 'User memiliki privilege yang dapat di-GRANT',
                        description:
                            "User {$grantee} memiliki privilege {$privilegeName} pada table {$privilege->TABLE_NAME} dan privilege tersebut dapat diberikan kepada user lain.",
                        objectType: 'TABLE',
                        objectName: $privilege->TABLE_NAME,
                        username: $grantee,
                        recommendation:
                            'Hapus GRANT OPTION jika user tidak membutuhkan kemampuan memberikan privilege kepada user lain.',
                        result: $result
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | DROP / DELETE / UPDATE
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $privilegeName,
                        [
                            'DROP',
                            'DELETE',
                            'UPDATE'
                        ],
                        true
                    )
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $privilege->TABLE_SCHEMA,
                        findingType: 'WRITE_PRIVILEGE',
                        category: 'PRIVILEGE',
                        severity: 'MEDIUM',
                        title: "User memiliki privilege {$privilegeName}",
                        description:
                            "User {$grantee} memiliki privilege {$privilegeName} terhadap table {$privilege->TABLE_NAME}.",
                        objectType: 'TABLE',
                        objectName: $privilege->TABLE_NAME,
                        username: $grantee,
                        recommendation:
                            'Pastikan privilege write memang diperlukan oleh account tersebut.',
                        result: $result
                    );
                }
            }

        } catch (Throwable) {
            /*
             * Privilege scanner tidak boleh menghentikan audit.
             */
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Scan sensitive columns
        |--------------------------------------------------------------------------
        */

        try {

            $columns = $db->select("
                SELECT
                    TABLE_NAME,
                    COLUMN_NAME,
                    DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
            ", [
                $connection->database
            ]);

            foreach ($columns as $column) {

                $name =
                    strtolower(
                        (string) $column->COLUMN_NAME
                    );


                /*
                |--------------------------------------------------------------------------
                | Email
                |--------------------------------------------------------------------------
                */

                if (
                    str_contains($name, 'email') ||
                    str_contains($name, 'e_mail')
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'SENSITIVE_DATA',
                        category: 'PII',
                        severity: 'MEDIUM',
                        title: 'Column berpotensi mengandung alamat email',
                        description:
                            "Column {$column->COLUMN_NAME} pada table {$column->TABLE_NAME} terdeteksi sebagai data PII.",
                        objectType: 'COLUMN',
                        objectName:
                            $column->TABLE_NAME .
                            '.' .
                            $column->COLUMN_NAME,
                        username: null,
                        recommendation:
                            'Pastikan akses terhadap data email dibatasi dan aktivitas akses dicatat.',
                        result: $result
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Phone
                |--------------------------------------------------------------------------
                */

                if (
                    str_contains($name, 'phone') ||
                    str_contains($name, 'telephone') ||
                    str_contains($name, 'mobile')
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'SENSITIVE_DATA',
                        category: 'PII',
                        severity: 'MEDIUM',
                        title: 'Column berpotensi mengandung nomor telepon',
                        description:
                            "Column {$column->COLUMN_NAME} pada table {$column->TABLE_NAME} terdeteksi sebagai data PII.",
                        objectType: 'COLUMN',
                        objectName:
                            $column->TABLE_NAME .
                            '.' .
                            $column->COLUMN_NAME,
                        username: null,
                        recommendation:
                            'Batasi akses nomor telepon dan monitor aktivitas pembacaan data.',
                        result: $result
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Password
                |--------------------------------------------------------------------------
                */

                if (
                    str_contains($name, 'password') ||
                    str_contains($name, 'passwd')
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'PASSWORD_COLUMN',
                        category: 'CREDENTIAL',
                        severity: 'HIGH',
                        title: 'Column password terdeteksi',
                        description:
                            "Column {$column->COLUMN_NAME} pada table {$column->TABLE_NAME} berpotensi menyimpan credential.",
                        objectType: 'COLUMN',
                        objectName:
                            $column->TABLE_NAME .
                            '.' .
                            $column->COLUMN_NAME,
                        username: null,
                        recommendation:
                            'Pastikan password disimpan menggunakan hashing yang aman dan jangan menyimpan password plaintext.',
                        result: $result
                    );
                }
            }

        } catch (Throwable) {
            /*
             * Sensitive scanner tidak menghentikan audit.
             */
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PostgreSQL
    |--------------------------------------------------------------------------
    */

    protected function scanPostgreSql(
        $db,
        DatabaseConnection $connection,
        array &$result
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Database users
        |--------------------------------------------------------------------------
        */

        try {

            $users = $db->select("
                SELECT
                    rolname,
                    rolsuper,
                    rolcreaterole,
                    rolcreatedb,
                    rolcanlogin
                FROM pg_roles
            ");

            foreach ($users as $user) {

                if ($user->rolsuper) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'SUPERUSER',
                        category: 'PRIVILEGE',
                        severity: 'HIGH',
                        title: 'PostgreSQL superuser terdeteksi',
                        description:
                            "Role {$user->rolname} memiliki SUPERUSER privilege.",
                        objectType: 'ROLE',
                        objectName: $user->rolname,
                        username: $user->rolname,
                        recommendation:
                            'Hindari penggunaan SUPERUSER untuk aplikasi biasa.',
                        result: $result
                    );
                }


                if ($user->rolcreaterole) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'CREATE_ROLE',
                        category: 'PRIVILEGE',
                        severity: 'HIGH',
                        title: 'User dapat membuat database role',
                        description:
                            "Role {$user->rolname} memiliki kemampuan CREATEROLE.",
                        objectType: 'ROLE',
                        objectName: $user->rolname,
                        username: $user->rolname,
                        recommendation:
                            'Hapus CREATEROLE jika tidak diperlukan.',
                        result: $result
                    );
                }


                if ($user->rolcreatedb) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'CREATE_DATABASE',
                        category: 'PRIVILEGE',
                        severity: 'MEDIUM',
                        title: 'User dapat membuat database',
                        description:
                            "Role {$user->rolname} memiliki CREATEDB privilege.",
                        objectType: 'ROLE',
                        objectName: $user->rolname,
                        username: $user->rolname,
                        recommendation:
                            'Batasi CREATEDB hanya untuk administrator database.',
                        result: $result
                    );
                }
            }

        } catch (Throwable) {
        }


        /*
        |--------------------------------------------------------------------------
        | Sensitive columns
        |--------------------------------------------------------------------------
        */

        try {

            $columns = $db->select("
                SELECT
                    table_name,
                    column_name,
                    data_type
                FROM information_schema.columns
                WHERE table_schema = 'public'
            ");

            foreach ($columns as $column) {

                $name = strtolower(
                    $column->column_name
                );

                $object =
                    $column->table_name .
                    '.' .
                    $column->column_name;


                if (
                    str_contains($name, 'email') ||
                    str_contains($name, 'phone') ||
                    str_contains($name, 'mobile')
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'SENSITIVE_DATA',
                        category: 'PII',
                        severity: 'MEDIUM',
                        title: 'Potential PII column terdeteksi',
                        description:
                            "Column {$object} berpotensi mengandung data pribadi.",
                        objectType: 'COLUMN',
                        objectName: $object,
                        username: null,
                        recommendation:
                            'Batasi akses dan monitor penggunaan data tersebut.',
                        result: $result
                    );
                }


                if (
                    str_contains($name, 'password') ||
                    str_contains($name, 'passwd')
                ) {

                    $this->createFinding(
                        connection: $connection,
                        databaseName: $connection->database,
                        findingType: 'PASSWORD_COLUMN',
                        category: 'CREDENTIAL',
                        severity: 'HIGH',
                        title: 'Potential password column terdeteksi',
                        description:
                            "Column {$object} berpotensi menyimpan credential.",
                        objectType: 'COLUMN',
                        objectName: $object,
                        username: null,
                        recommendation:
                            'Pastikan credential tidak disimpan dalam plaintext.',
                        result: $result
                    );
                }
            }

        } catch (Throwable) {
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Finding
    |--------------------------------------------------------------------------
    */

    protected function createFinding(
        DatabaseConnection $connection,
        ?string $databaseName,
        string $findingType,
        string $category,
        string $severity,
        string $title,
        ?string $description,
        ?string $objectType,
        ?string $objectName,
        ?string $username,
        ?string $recommendation,
        array &$result
    ): void {

        SecurityFinding::create([
            'database_connection_id' =>
                $connection->id,

            'database_name' =>
                $databaseName,

            'finding_type' =>
                $findingType,

            'category' =>
                $category,

            'severity' =>
                $severity,

            'title' =>
                $title,

            'description' =>
                $description,

            'object_type' =>
                $objectType,

            'object_name' =>
                $objectName,

            'username' =>
                $username,

            'recommendation' =>
                $recommendation,

            'status' =>
                'OPEN',

            'detected_at' =>
                now(),
        ]);


        $key = strtolower($severity);

        if (isset($result[$key])) {
            $result[$key]++;
        }
    }
}