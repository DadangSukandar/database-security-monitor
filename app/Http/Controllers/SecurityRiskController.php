<?php

namespace App\Http\Controllers;

use App\Models\SecurityFinding;
use App\Models\VulnerabilityAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityRiskController extends Controller
{
    /**
     * =========================================================
     * SECURITY RISK INTELLIGENCE
     * =========================================================
     */
    public function index(Request $request)
    {
        /*
         * =====================================================
         * FILTER PERIOD
         * =====================================================
         */

        $period = $request->input('period', '30');

        if (!in_array($period, ['7', '30', '90', '365'])) {
            $period = '30';
        }

        $startDate = now()
            ->subDays((int) $period)
            ->startOfDay();

        $endDate = now()->endOfDay();


        /*
         * =====================================================
         * TOTAL FINDINGS
         * =====================================================
         */

        $totalFindings = SecurityFinding::count();


        /*
         * =====================================================
         * RESOLVED FINDINGS
         *
         * Kita dukung dua model:
         *
         * 1. resolved = true
         * 2. status = RESOLVED
         * =====================================================
         */

        $resolvedFindings = SecurityFinding::query()
            ->where(function ($query) {

                $query
                    ->where('resolved', true)
                    ->orWhereRaw(
                        'UPPER(COALESCE(status, \'\')) = ?',
                        ['RESOLVED']
                    );

            })
            ->count();


        /*
         * =====================================================
         * IGNORED FINDINGS
         * =====================================================
         */

        $ignoredFindings = SecurityFinding::query()
            ->where(function ($query) {

                $query
                    ->whereRaw(
                        'UPPER(COALESCE(status, \'\')) = ?',
                        ['IGNORED']
                    );

            })
            ->count();


        /*
         * =====================================================
         * ACTIVE FINDINGS
         *
         * Prioritas utama:
         *
         * status OPEN
         *
         * Kemudian fallback:
         *
         * resolved = false
         * =====================================================
         */

        $activeQuery = SecurityFinding::query()
            ->where(function ($query) {

                $query
                    ->whereRaw(
                        'UPPER(COALESCE(status, \'\')) IN (?, ?)',
                        [
                            'OPEN',
                            'ACTIVE'
                        ]
                    )
                    ->orWhere(function ($subQuery) {

                        $subQuery
                            ->where(function ($q) {

                                $q
                                    ->whereNull('status')
                                    ->orWhere(
                                        'status',
                                        ''
                                    );

                            })
                            ->where(function ($q) {

                                $q
                                    ->where(
                                        'resolved',
                                        false
                                    )
                                    ->orWhereNull(
                                        'resolved'
                                    );

                            });

                    });

            })
            ->where(function ($query) {

                $query
                    ->where('resolved', false)
                    ->orWhereNull('resolved')
                    ->orWhereRaw(
                        'UPPER(COALESCE(status, \'\')) IN (?, ?)',
                        [
                            'OPEN',
                            'ACTIVE'
                        ]
                    );

            });


        /*
         * =====================================================
         * ACTIVE FINDINGS COUNT
         * =====================================================
         */

        $openFindings = (clone $activeQuery)
            ->count();


        /*
         * =====================================================
         * ACTIVE CRITICAL
         * =====================================================
         */

        $openCritical = (clone $activeQuery)
            ->whereRaw(
                'UPPER(COALESCE(severity, \'\')) = ?',
                ['CRITICAL']
            )
            ->count();


        /*
         * =====================================================
         * ACTIVE HIGH
         * =====================================================
         */

        $openHigh = (clone $activeQuery)
            ->whereRaw(
                'UPPER(COALESCE(severity, \'\')) = ?',
                ['HIGH']
            )
            ->count();


        /*
         * =====================================================
         * ACTIVE MEDIUM
         * =====================================================
         */

        $openMedium = (clone $activeQuery)
            ->whereRaw(
                'UPPER(COALESCE(severity, \'\')) = ?',
                ['MEDIUM']
            )
            ->count();


        /*
         * =====================================================
         * ACTIVE LOW
         * =====================================================
         */

        $openLow = (clone $activeQuery)
            ->whereRaw(
                'UPPER(COALESCE(severity, \'\')) = ?',
                ['LOW']
            )
            ->count();


        /*
         * =====================================================
         * RISK POINTS
         *
         * CRITICAL = 40
         * HIGH     = 20
         * MEDIUM   = 10
         * LOW      = 3
         *
         * Maksimal score tetap 100.
         * =====================================================
         */

        $riskPoints =
            ($openCritical * 40) +
            ($openHigh * 20) +
            ($openMedium * 10) +
            ($openLow * 3);


        /*
         * =====================================================
         * SECURITY SCORE
         * =====================================================
         */

        $securityScore = max(
            0,
            min(
                100,
                100 - $riskPoints
            )
        );


        /*
         * =====================================================
         * RISK LEVEL
         * =====================================================
         */

        if ($securityScore <= 39) {

            $riskLevel = 'CRITICAL';

            $scoreLabel =
                'Critical Security Risk';

        } elseif ($securityScore <= 59) {

            $riskLevel = 'HIGH';

            $scoreLabel =
                'High Security Risk';

        } elseif ($securityScore <= 79) {

            $riskLevel = 'MEDIUM';

            $scoreLabel =
                'Moderate Security Risk';

        } elseif ($securityScore <= 94) {

            $riskLevel = 'LOW';

            $scoreLabel =
                'Low Security Risk';

        } else {

            $riskLevel = 'SECURE';

            $scoreLabel =
                'Good Security Posture';
        }


        /*
         * =====================================================
         * RISK STATUS
         * =====================================================
         */

        if ($riskPoints === 0) {

            $riskStatus =
                'No active risk points';

        } elseif ($riskPoints <= 20) {

            $riskStatus =
                'Low risk exposure';

        } elseif ($riskPoints <= 50) {

            $riskStatus =
                'Moderate risk exposure';

        } elseif ($riskPoints <= 100) {

            $riskStatus =
                'High risk exposure';

        } else {

            $riskStatus =
                'Critical risk exposure';
        }


        /*
         * =====================================================
         * LATEST ASSESSMENT
         * =====================================================
         */

        $latestAssessment =
            VulnerabilityAssessment::with(
                'databaseConnection'
            )
                ->latest('scanned_at')
                ->first();


        /*
         * =====================================================
         * TOP ACTIVE RISKS
         * =====================================================
         */

        $topRisks =
            (clone $activeQuery)
                ->orderByRaw(
                    "
                    CASE UPPER(severity)
                        WHEN 'CRITICAL' THEN 1
                        WHEN 'HIGH' THEN 2
                        WHEN 'MEDIUM' THEN 3
                        WHEN 'LOW' THEN 4
                        ELSE 5
                    END
                    "
                )
                ->latest('created_at')
                ->limit(10)
                ->get();


        /*
         * =====================================================
         * DATABASE RISK
         * =====================================================
         */

        $databaseRisk =
            (clone $activeQuery)
                ->select(
                    'database_name',
                    DB::raw(
                        'COUNT(*) as total'
                    )
                )
                ->groupBy(
                    'database_name'
                )
                ->orderByDesc('total')
                ->limit(10)
                ->get();


        /*
         * =====================================================
         * RISK CATEGORIES
         *
         * Semua findings, bukan hanya active.
         * =====================================================
         */

        $categoryDistribution =
            SecurityFinding::query()
                ->select(
                    'category',
                    DB::raw(
                        'COUNT(*) as total'
                    )
                )
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();


        /*
         * =====================================================
         * RECENT FINDINGS
         * =====================================================
         */

        $recentFindings =
            SecurityFinding::query()
                ->latest('created_at')
                ->limit(15)
                ->get();


        /*
         * =====================================================
         * RISK TREND
         * =====================================================
         */

        $riskTrend =
            VulnerabilityAssessment::query()
                ->whereBetween(
                    'scanned_at',
                    [
                        $startDate,
                        $endDate
                    ]
                )
                ->orderBy('scanned_at')
                ->get([
                    'id',
                    'score',
                    'critical_count',
                    'high_count',
                    'medium_count',
                    'low_count',
                    'scanned_at',
                ]);


        /*
         * =====================================================
         * CHART DATA
         * =====================================================
         */

        $chartLabels = [];

        $chartScores = [];

        $chartCritical = [];

        $chartHigh = [];

        $chartMedium = [];

        $chartLow = [];


        foreach ($riskTrend as $trend) {

            $chartLabels[] =
                $trend->scanned_at
                    ? $trend->scanned_at
                        ->format('d M Y')
                    : '-';


            $chartScores[] =
                (int) (
                    $trend->score ?? 0
                );


            $chartCritical[] =
                (int) (
                    $trend->critical_count ?? 0
                );


            $chartHigh[] =
                (int) (
                    $trend->high_count ?? 0
                );


            $chartMedium[] =
                (int) (
                    $trend->medium_count ?? 0
                );


            $chartLow[] =
                (int) (
                    $trend->low_count ?? 0
                );
        }


        /*
         * =====================================================
         * ASSESSMENT STATISTICS
         * =====================================================
         */

        $assessmentCount =
            VulnerabilityAssessment::count();


        $averageScore =
            VulnerabilityAssessment::query()
                ->whereNotNull('score')
                ->avg('score');


        $averageScore =
            $averageScore !== null
                ? round(
                    $averageScore,
                    1
                )
                : 0;


        /*
         * =====================================================
         * BEST SCORE
         * =====================================================
         */

        $bestScore =
            VulnerabilityAssessment::query()
                ->max('score');


        $bestScore =
            $bestScore !== null
                ? (int) $bestScore
                : 0;


        /*
         * =====================================================
         * WORST SCORE
         * =====================================================
         */

        $worstScore =
            VulnerabilityAssessment::query()
                ->min('score');


        $worstScore =
            $worstScore !== null
                ? (int) $worstScore
                : 0;


        /*
         * =====================================================
         * RETURN VIEW
         * =====================================================
         */

        return view(
            'security-risk.index',
            compact(

                'period',

                'totalFindings',

                'openFindings',

                'resolvedFindings',

                'ignoredFindings',

                'openCritical',

                'openHigh',

                'openMedium',

                'openLow',

                'riskPoints',

                'securityScore',

                'riskLevel',

                'scoreLabel',

                'riskStatus',

                'latestAssessment',

                'topRisks',

                'databaseRisk',

                'categoryDistribution',

                'recentFindings',

                'riskTrend',

                'chartLabels',

                'chartScores',

                'chartCritical',

                'chartHigh',

                'chartMedium',

                'chartLow',

                'assessmentCount',

                'averageScore',

                'bestScore',

                'worstScore'
            )
        );
    }
}