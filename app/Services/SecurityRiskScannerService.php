<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use App\Models\DatabasePrivilege;
use App\Models\SecurityRisk;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use App\Models\DiscoveredColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SecurityRiskScannerService
{
    /**
     * Scan seluruh security risk untuk satu connection.
     */
    public function scan(
        DatabaseConnection $connection
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Hapus hasil scan sebelumnya
        |--------------------------------------------------------------------------
        */

        SecurityRisk::where(
            'database_connection_id',
            $connection->id
        )->delete();


        /*
        |--------------------------------------------------------------------------
        | Ambil privileges
        |--------------------------------------------------------------------------
        */

        $privileges = DatabasePrivilege::query()
            ->where(
                'database_connection_id',
                $connection->id
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Jika belum ada privilege
        |--------------------------------------------------------------------------
        */

        if ($privileges->isEmpty()) {

            return [
                'total' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Ambil discovered databases
        |--------------------------------------------------------------------------
        */

        $databases =
            DiscoveredDatabase::query()
                ->where(
                    'database_connection_id',
                    $connection->id
                )
                ->with('tables.columns')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Scan
        |--------------------------------------------------------------------------
        */

        foreach ($privileges as $privilege) {

            $this->processPrivilege(
                $connection,
                $privilege,
                $databases
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        return [

            'total' =>
                SecurityRisk::where(
                    'database_connection_id',
                    $connection->id
                )->count(),

            'critical' =>
                SecurityRisk::where(
                    'database_connection_id',
                    $connection->id
                )
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->count(),

            'high' =>
                SecurityRisk::where(
                    'database_connection_id',
                    $connection->id
                )
                ->where(
                    'risk_level',
                    'HIGH'
                )
                ->count(),

            'medium' =>
                SecurityRisk::where(
                    'database_connection_id',
                    $connection->id
                )
                ->where(
                    'risk_level',
                    'MEDIUM'
                )
                ->count(),

            'low' =>
                SecurityRisk::where(
                    'database_connection_id',
                    $connection->id
                )
                ->where(
                    'risk_level',
                    'LOW'
                )
                ->count(),
        ];
    }


    /**
     * Proses satu privilege.
     */
    private function processPrivilege(
        DatabaseConnection $connection,
        DatabasePrivilege $privilege,
        Collection $databases
    ): void {

        $databaseName =
            $this->normalizeValue(
                $privilege->database_name
            );

        $tableName =
            $this->normalizeValue(
                $privilege->table_name
            );


        /*
        |--------------------------------------------------------------------------
        | Cari database yang terkena privilege
        |--------------------------------------------------------------------------
        */

        foreach ($databases as $database) {

            if (
                !$this->matches(
                    $database->name,
                    $databaseName
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Semua table
            |--------------------------------------------------------------------------
            */

            foreach ($database->tables as $table) {

                if (
                    !$this->tableMatches(
                        $table->name,
                        $tableName
                    )
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Cari sensitive columns
                |--------------------------------------------------------------------------
                */

                foreach ($table->columns as $column) {

                    $finding =
                        $this->detectSensitiveColumn(
                            $column
                        );


                    if (!$finding) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Hitung risk
                    |--------------------------------------------------------------------------
                    */

                    $risk =
                        $this->calculateRisk(
                            $privilege,
                            $finding
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Simpan
                    |--------------------------------------------------------------------------
                    */

                    SecurityRisk::create([

                        'database_connection_id' =>
                            $connection->id,

                        'username' =>
                            $privilege->username,

                        'host' =>
                            $privilege->host,

                        'database_name' =>
                            $database->name,

                        'schema_name' =>
                            $table->schema_name,

                        'table_name' =>
                            $table->name,

                        'column_name' =>
                            $column->name,

                        'privilege' =>
                            $privilege->privilege,

                        'is_grantable' =>
                            (bool) $privilege->is_grantable,

                        'sensitive_category' =>
                            $finding['category'],

                        'sensitive_rule' =>
                            $finding['rule'],

                        'risk_level' =>
                            $risk['level'],

                        'risk_reason' =>
                            $risk['reason'],

                        'is_resolved' =>
                            false,

                        'last_scanned_at' =>
                            now(),
                    ]);
                }
            }
        }
    }


    /**
     * Deteksi apakah column merupakan sensitive data.
     */
    private function detectSensitiveColumn(
        $column
    ): ?array {

        $name =
            strtolower(
                trim(
                    (string) $column->name
                )
            );


        $type =
            strtolower(
                trim(
                    (string) $column->data_type
                )
            );


        /*
        |--------------------------------------------------------------------------
        | EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'email',
                    'e_mail',
                    'mail_address',
                    'email_address',
                ]
            )
        ) {

            return [
                'category' => 'PII',
                'rule' => 'EMAIL_ADDRESS',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PHONE
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'phone',
                    'telephone',
                    'mobile',
                    'mobile_phone',
                    'phone_number',
                    'tel',
                    'contact_number',
                ]
            )
        ) {

            return [
                'category' => 'PII',
                'rule' => 'PHONE_NUMBER',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | NAME
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'full_name',
                    'fullname',
                    'customer_name',
                    'first_name',
                    'last_name',
                    'nama',
                    'nama_lengkap',
                ]
            )
        ) {

            return [
                'category' => 'PII',
                'rule' => 'PERSON_NAME',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | ADDRESS
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'address',
                    'alamat',
                    'home_address',
                    'street',
                    'street_address',
                ]
            )
        ) {

            return [
                'category' => 'PII',
                'rule' => 'ADDRESS',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | NIK / NATIONAL ID
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'nik',
                    'national_id',
                    'identity_number',
                    'id_number',
                    'no_ktp',
                    'ktp',
                ]
            )
        ) {

            return [
                'category' => 'IDENTITY',
                'rule' => 'NATIONAL_ID',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | CREDIT CARD
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'credit_card',
                    'card_number',
                    'cc_number',
                    'creditcard',
                ]
            )
        ) {

            return [
                'category' => 'FINANCIAL',
                'rule' => 'CREDIT_CARD',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | BANK ACCOUNT
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'bank_account',
                    'account_number',
                    'rekening',
                    'rekening_bank',
                ]
            )
        ) {

            return [
                'category' => 'FINANCIAL',
                'rule' => 'BANK_ACCOUNT',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD
        |--------------------------------------------------------------------------
        */

        if (
            $this->containsAny(
                $name,
                [
                    'password',
                    'passwd',
                    'pass_hash',
                    'password_hash',
                ]
            )
        ) {

            return [
                'category' => 'CREDENTIAL',
                'rule' => 'PASSWORD',
            ];
        }


        return null;
    }


    /**
     * Hitung risk berdasarkan privilege + sensitive data.
     */
    private function calculateRisk(
        DatabasePrivilege $privilege,
        array $finding
    ): array {

        $action =
            strtoupper(
                trim(
                    (string) $privilege->privilege
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Critical
        |--------------------------------------------------------------------------
        */

        if (
            $finding['rule'] === 'CREDIT_CARD' &&
            in_array(
                $action,
                [
                    'SELECT',
                    'INSERT',
                    'UPDATE',
                    'DELETE',
                    'ALL',
                ],
                true
            )
        ) {

            return [
                'level' => 'CRITICAL',

                'reason' =>
                    'User memiliki akses terhadap data kartu pembayaran sensitif.',
            ];
        }


        if (
            $finding['rule'] === 'PASSWORD' &&
            in_array(
                $action,
                [
                    'SELECT',
                    'UPDATE',
                    'ALL',
                ],
                true
            )
        ) {

            return [
                'level' => 'CRITICAL',

                'reason' =>
                    'User memiliki akses terhadap credential/password.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | High
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $action,
                [
                    'UPDATE',
                    'DELETE',
                    'DROP',
                    'ALTER',
                    'INSERT',
                    'ALL',
                    'GRANT',
                ],
                true
            )
        ) {

            return [
                'level' => 'HIGH',

                'reason' =>
                    'User memiliki privilege perubahan terhadap column sensitif.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Grantable
        |--------------------------------------------------------------------------
        */

        if (
            $privilege->is_grantable
        ) {

            return [
                'level' => 'HIGH',

                'reason' =>
                    'User dapat memberikan privilege kepada user lain.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Medium
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'SELECT'
        ) {

            return [
                'level' => 'MEDIUM',

                'reason' =>
                    'User dapat membaca data sensitif.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */

        return [
            'level' => 'LOW',

            'reason' =>
                'User memiliki akses terhadap data yang terdeteksi sensitif.',
        ];
    }


    /**
     * Cocokkan database.
     */
    private function matches(
        string $actual,
        string $requested
    ): bool {

        if (
            $requested === '' ||
            $requested === '*'
        ) {
            return true;
        }


        return strcasecmp(
            $actual,
            $requested
        ) === 0;
    }


    /**
     * Cocokkan table.
     */
    private function tableMatches(
        string $actual,
        string $requested
    ): bool {

        if (
            $requested === '' ||
            $requested === '*'
        ) {
            return true;
        }


        return strcasecmp(
            $actual,
            $requested
        ) === 0;
    }


    /**
     * Normalisasi nama database/table.
     */
    private function normalizeValue(
        mixed $value
    ): string {

        $value =
            trim(
                (string) $value
            );


        if (
            $value === '%' ||
            strtoupper($value) === 'ALL'
        ) {
            return '*';
        }


        return $value;
    }


    /**
     * Cek beberapa keyword.
     */
    private function containsAny(
        string $value,
        array $keywords
    ): bool {

        foreach ($keywords as $keyword) {

            if (
                str_contains(
                    $value,
                    strtolower($keyword)
                )
            ) {
                return true;
            }
        }


        return false;
    }
}