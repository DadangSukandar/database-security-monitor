<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;

class SecurityScoreService
{
    /**
     * Calculate security score.
     *
     * @param DatabaseConnection|null $connection
     * @return array
     */
    public function calculate(
        ?DatabaseConnection $connection = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Query Security Findings
        |--------------------------------------------------------------------------
        */

        $query = SecurityFinding::query()
            ->where('status', 'OPEN');


        /*
        |--------------------------------------------------------------------------
        | Filter berdasarkan database connection
        |--------------------------------------------------------------------------
        */

        if ($connection !== null) {

            $query->where(
                'database_connection_id',
                $connection->id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Ambil findings
        |--------------------------------------------------------------------------
        */

        $findings = $query->get();


        /*
        |--------------------------------------------------------------------------
        | Hitung severity
        |--------------------------------------------------------------------------
        */

        $critical = $findings
            ->where('severity', 'CRITICAL')
            ->count();

        $high = $findings
            ->where('severity', 'HIGH')
            ->count();

        $medium = $findings
            ->where('severity', 'MEDIUM')
            ->count();

        $low = $findings
            ->where('severity', 'LOW')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $total = $findings->count();


        /*
        |--------------------------------------------------------------------------
        | Calculate Score
        |--------------------------------------------------------------------------
        |
        | Score awal = 100
        |
        | CRITICAL = -25
        | HIGH     = -15
        | MEDIUM   = -7
        | LOW      = -2
        |
        */

        $score = 100;

        $score -= $critical * 25;

        $score -= $high * 15;

        $score -= $medium * 7;

        $score -= $low * 2;


        /*
        |--------------------------------------------------------------------------
        | Pastikan score 0 - 100
        |--------------------------------------------------------------------------
        */

        $score = max(
            0,
            min(
                100,
                $score
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Security Level
        |--------------------------------------------------------------------------
        */

        if ($score >= 90) {

            $level = 'EXCELLENT';

        } elseif ($score >= 75) {

            $level = 'GOOD';

        } elseif ($score >= 50) {

            $level = 'WARNING';

        } else {

            $level = 'CRITICAL';
        }


        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [

            'score' =>
                $score,

            'level' =>
                $level,

            'total' =>
                $total,

            'critical' =>
                $critical,

            'high' =>
                $high,

            'medium' =>
                $medium,

            'low' =>
                $low,

        ];
    }
}