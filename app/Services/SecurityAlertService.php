<?php

namespace App\Services;

use App\Models\DatabaseActivity;
use App\Models\SecurityAlert;
use Illuminate\Support\Str;

class SecurityAlertService
{
    public function analyze(DatabaseActivity $activity): ?SecurityAlert
    {
        $query = trim((string) $activity->query);

        if ($query === '') {
            return null;
        }

        $normalized = strtoupper(
            preg_replace('/\s+/', ' ', $query)
        );

        $rules = [

            [
                'pattern' => '/\bDROP\s+DATABASE\b/i',
                'type' => 'DROP_DATABASE',
                'severity' => 'CRITICAL',
                'title' => 'DROP DATABASE detected',
                'description' =>
                    'A database deletion command was detected.'
            ],

            [
                'pattern' => '/\bDROP\s+TABLE\b/i',
                'type' => 'DROP_TABLE',
                'severity' => 'CRITICAL',
                'title' => 'DROP TABLE detected',
                'description' =>
                    'A table deletion command was detected.'
            ],

            [
                'pattern' => '/\bTRUNCATE\s+TABLE\b/i',
                'type' => 'TRUNCATE_TABLE',
                'severity' => 'CRITICAL',
                'title' => 'TRUNCATE TABLE detected',
                'description' =>
                    'A table truncation command was detected.'
            ],

            [
                'pattern' => '/\bGRANT\b/i',
                'type' => 'GRANT_PRIVILEGE',
                'severity' => 'HIGH',
                'title' => 'GRANT privilege detected',
                'description' =>
                    'A privilege grant operation was detected.'
            ],

            [
                'pattern' => '/\bREVOKE\b/i',
                'type' => 'REVOKE_PRIVILEGE',
                'severity' => 'HIGH',
                'title' => 'REVOKE privilege detected',
                'description' =>
                    'A privilege revoke operation was detected.'
            ],

            [
                'pattern' => '/\bCREATE\s+USER\b/i',
                'type' => 'CREATE_USER',
                'severity' => 'HIGH',
                'title' => 'CREATE USER detected',
                'description' =>
                    'A database user creation operation was detected.'
            ],

            [
                'pattern' => '/\bDROP\s+USER\b/i',
                'type' => 'DROP_USER',
                'severity' => 'CRITICAL',
                'title' => 'DROP USER detected',
                'description' =>
                    'A database user deletion operation was detected.'
            ],

            [
                'pattern' => '/\bALTER\s+TABLE\b/i',
                'type' => 'ALTER_TABLE',
                'severity' => 'HIGH',
                'title' => 'ALTER TABLE detected',
                'description' =>
                    'A table structure modification was detected.'
            ],

            [
                'pattern' => '/\bCREATE\s+DATABASE\b/i',
                'type' => 'CREATE_DATABASE',
                'severity' => 'HIGH',
                'title' => 'CREATE DATABASE detected',
                'description' =>
                    'A database creation operation was detected.'
            ],
        ];

        foreach ($rules as $rule) {

            if (preg_match($rule['pattern'], $normalized)) {

                return SecurityAlert::create([
                    'database_activity_id' =>
                        $activity->id,

                    'database_connection_id' =>
                        $activity->database_connection_id,

                    'database_name' =>
                        $activity->database_name,

                    'username' =>
                        $activity->username,

                    'client_ip' =>
                        $activity->client_ip,

                    'alert_type' =>
                        $rule['type'],

                    'severity' =>
                        $rule['severity'],

                    'title' =>
                        $rule['title'],

                    'description' =>
                        $rule['description'],

                    'query' =>
                        $query,

                    'table_name' =>
                        $activity->table_name,

                    'status' =>
                        'OPEN',

                    'detected_at' =>
                        $activity->executed_at ?? now(),
                ]);
            }
        }

        /*
         * UPDATE tanpa WHERE
         */
        if (
            preg_match('/\bUPDATE\b/i', $normalized) &&
            !preg_match('/\bWHERE\b/i', $normalized)
        ) {

            return SecurityAlert::create([
                'database_activity_id' =>
                    $activity->id,

                'database_connection_id' =>
                    $activity->database_connection_id,

                'database_name' =>
                    $activity->database_name,

                'username' =>
                    $activity->username,

                'client_ip' =>
                    $activity->client_ip,

                'alert_type' =>
                    'UPDATE_WITHOUT_WHERE',

                'severity' =>
                    'MEDIUM',

                'title' =>
                    'UPDATE without WHERE detected',

                'description' =>
                    'An UPDATE query without a WHERE condition was detected.',

                'query' =>
                    $query,

                'table_name' =>
                    $activity->table_name,

                'status' =>
                    'OPEN',

                'detected_at' =>
                    $activity->executed_at ?? now(),
            ]);
        }

        /*
         * DELETE tanpa WHERE
         */
        if (
            preg_match('/\bDELETE\s+FROM\b/i', $normalized) &&
            !preg_match('/\bWHERE\b/i', $normalized)
        ) {

            return SecurityAlert::create([
                'database_activity_id' =>
                    $activity->id,

                'database_connection_id' =>
                    $activity->database_connection_id,

                'database_name' =>
                    $activity->database_name,

                'username' =>
                    $activity->username,

                'client_ip' =>
                    $activity->client_ip,

                'alert_type' =>
                    'DELETE_WITHOUT_WHERE',

                'severity' =>
                    'HIGH',

                'title' =>
                    'DELETE without WHERE detected',

                'description' =>
                    'A DELETE query without a WHERE condition was detected.',

                'query' =>
                    $query,

                'table_name' =>
                    $activity->table_name,

                'status' =>
                    'OPEN',

                'detected_at' =>
                    $activity->executed_at ?? now(),
            ]);
        }

        return null;
    }
}