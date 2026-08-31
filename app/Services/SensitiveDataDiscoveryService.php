<?php

namespace App\Services;

use App\Models\DiscoveredColumn;
use App\Models\SensitiveDataFinding;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SensitiveDataDiscoveryService
{
    public function scan(): array
    {
        $columns = DiscoveredColumn::query()
            ->with([
                'table.database.databaseConnection'
            ])
            ->get();

        $created = 0;

        foreach ($columns as $column) {

            $findings = $this->classify($column);

            /*
             * Hapus hasil lama agar scan
             * selalu menghasilkan data terbaru.
             */
            SensitiveDataFinding::where(
                'discovered_column_id',
                $column->id
            )->delete();

            foreach ($findings as $finding) {

                SensitiveDataFinding::create([
                    'discovered_column_id' =>
                        $column->id,

                    'category' =>
                        $finding['category'],

                    'risk_level' =>
                        $finding['risk_level'],

                    'rule_name' =>
                        $finding['rule_name'],

                    'description' =>
                        $finding['description'],
                ]);

                $created++;
            }
        }

        return [
            'columns' => $columns->count(),
            'findings' => $created,
        ];
    }

    private function classify(
        DiscoveredColumn $column
    ): array {

        $name = Str::lower(
            $column->name
        );

        $type = Str::lower(
            $column->data_type ?? ''
        );

        $results = [];

        /*
        |--------------------------------------------------------------------------
        | PASSWORD
        |--------------------------------------------------------------------------
        */

        if ($this->matches($name, [
            'password',
            'passwd',
            'pwd',
            'password_hash',
            'pass_hash',
        ])) {

            $results[] = [
                'category' => 'CREDENTIAL',
                'risk_level' => 'HIGH',
                'rule_name' => 'PASSWORD_FIELD',
                'description' =>
                    'Column kemungkinan menyimpan password atau password hash.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | API KEY / SECRET / TOKEN
        |--------------------------------------------------------------------------
        */

        if ($this->matches($name, [
            'api_key',
            'apikey',
            'api_secret',
            'secret_key',
            'access_token',
            'refresh_token',
            'auth_token',
            'bearer_token',
        ])) {

            $results[] = [
                'category' => 'CREDENTIAL',
                'risk_level' => 'HIGH',
                'rule_name' => 'SECRET_OR_TOKEN',
                'description' =>
                    'Column kemungkinan mengandung credential, secret, atau token.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'email',
                'email_address',
                'mail',
                'user_email',
            ])
        ) {

            $results[] = [
                'category' => 'PII',
                'risk_level' => 'MEDIUM',
                'rule_name' => 'EMAIL_ADDRESS',
                'description' =>
                    'Column kemungkinan berisi alamat email.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PHONE
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'phone',
                'phone_number',
                'mobile',
                'mobile_number',
                'telephone',
                'tel',
                'whatsapp',
            ])
        ) {

            $results[] = [
                'category' => 'PII',
                'risk_level' => 'MEDIUM',
                'rule_name' => 'PHONE_NUMBER',
                'description' =>
                    'Column kemungkinan berisi nomor telepon.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | IDENTITY
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'nik',
                'national_id',
                'identity_number',
                'identity_no',
                'passport',
                'passport_number',
                'ktp',
            ])
        ) {

            $results[] = [
                'category' => 'IDENTITY',
                'risk_level' => 'HIGH',
                'rule_name' => 'IDENTITY_NUMBER',
                'description' =>
                    'Column kemungkinan berisi nomor identitas.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | ADDRESS
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'address',
                'home_address',
                'street',
                'street_address',
                'postal_address',
            ])
        ) {

            $results[] = [
                'category' => 'PII',
                'risk_level' => 'MEDIUM',
                'rule_name' => 'ADDRESS',
                'description' =>
                    'Column kemungkinan berisi alamat.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | DATE OF BIRTH
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'dob',
                'birth_date',
                'date_of_birth',
                'birthdate',
            ])
        ) {

            $results[] = [
                'category' => 'PII',
                'risk_level' => 'MEDIUM',
                'rule_name' => 'DATE_OF_BIRTH',
                'description' =>
                    'Column kemungkinan berisi tanggal lahir.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | CREDIT CARD
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'card_number',
                'credit_card',
                'credit_card_number',
                'card_no',
            ])
        ) {

            $results[] = [
                'category' => 'FINANCIAL',
                'risk_level' => 'CRITICAL',
                'rule_name' => 'CREDIT_CARD',
                'description' =>
                    'Column kemungkinan berisi nomor kartu pembayaran.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | BANK ACCOUNT
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'bank_account',
                'bank_account_number',
                'account_number',
                'rekening',
                'rekening_number',
            ])
        ) {

            $results[] = [
                'category' => 'FINANCIAL',
                'risk_level' => 'HIGH',
                'rule_name' => 'BANK_ACCOUNT',
                'description' =>
                    'Column kemungkinan berisi nomor rekening.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | NAME
        |--------------------------------------------------------------------------
        */

        if (
            $this->matches($name, [
                'full_name',
                'first_name',
                'last_name',
                'customer_name',
                'employee_name',
            ])
        ) {

            $results[] = [
                'category' => 'PII',
                'risk_level' => 'LOW',
                'rule_name' => 'PERSON_NAME',
                'description' =>
                    'Column kemungkinan berisi nama seseorang.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | UUID
        |--------------------------------------------------------------------------
        */

        if (
            $type === 'uuid' ||
            $this->matches($name, [
                'uuid',
                'user_uuid',
                'customer_uuid',
            ])
        ) {

            $results[] = [
                'category' => 'IDENTIFIER',
                'risk_level' => 'LOW',
                'rule_name' => 'UUID_IDENTIFIER',
                'description' =>
                    'Column kemungkinan digunakan sebagai identifier.',
            ];
        }


        return $results;
    }

    private function matches(
        string $columnName,
        array $patterns
    ): bool {

        foreach ($patterns as $pattern) {

            if (
                $columnName === $pattern ||
                Str::contains(
                    $columnName,
                    $pattern
                )
            ) {
                return true;
            }
        }

        return false;
    }
}