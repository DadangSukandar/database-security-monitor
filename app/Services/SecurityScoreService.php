<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;

class SecurityScoreService
{
    /**
     * Calculate security score.
     */
    public function calculate(
        int $teamId,
        ?DatabaseConnection $connection = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Query Security Findings
        |--------------------------------------------------------------------------
        */

        $query =
            SecurityFinding::query()
                ->forTeam($teamId)
                ->where(
                    'status',
                    'OPEN'
                );

        if ($connection !== null) {
            $query->where(
                'database_connection_id',
                $connection->id
            );
        }

        $stats =
            $query
                ->selectRaw(
                    "
                    COUNT(*) AS total,
                    SUM(
                        CASE
                            WHEN severity = 'CRITICAL'
                            THEN 1
                            ELSE 0
                        END
                    ) AS critical,
                    SUM(
                        CASE
                            WHEN severity = 'HIGH'
                            THEN 1
                            ELSE 0
                        END
                    ) AS high,
                    SUM(
                        CASE
                            WHEN severity = 'MEDIUM'
                            THEN 1
                            ELSE 0
                        END
                    ) AS medium,
                    SUM(
                        CASE
                            WHEN severity = 'LOW'
                            THEN 1
                            ELSE 0
                        END
                    ) AS low
                    "
                )
                ->first();

        $total =
            (int) ($stats?->total ?? 0);

        $critical =
            (int) ($stats?->critical ?? 0);

        $high =
            (int) ($stats?->high ?? 0);

        $medium =
            (int) ($stats?->medium ?? 0);

        $low =
            (int) ($stats?->low ?? 0);

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

            'score' => $score,

            'level' => $level,

            'total' => $total,

            'critical' => $critical,

            'high' => $high,

            'medium' => $medium,

            'low' => $low,

        ];
    }
}
