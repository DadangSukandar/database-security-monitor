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

        $teamId = (int) $request->user()->current_team_id;

        $teamFindingQuery = SecurityFinding::query()
            ->forTeam($teamId);

        $teamAssessmentQuery = VulnerabilityAssessment::query()
            ->forTeam($teamId);
        /*
         * =====================================================
         * FILTER PERIOD
         * =====================================================
         */

        $period = $request->input('period', '30');

        if (! in_array($period, ['7', '30', '90', '365'])) {
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

        $findingStats =
            (clone $teamFindingQuery)
                ->selectRaw(
                    "
                    COUNT(*) AS total_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(status, '')) = 'RESOLVED'
                                    OR resolved_at IS NOT NULL
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS resolved_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(status, '')) = 'IGNORED'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS ignored_findings
                    "
                )
                ->first();

        $totalFindings =
            (int) ($findingStats?->total_findings ?? 0);

        $resolvedFindings =
            (int) ($findingStats?->resolved_findings ?? 0);

        $ignoredFindings =
            (int) ($findingStats?->ignored_findings ?? 0);

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

        $activeQuery =
            (clone $teamFindingQuery)
                ->where(function ($query) {
                    $query
                        ->whereRaw(
                            'UPPER(COALESCE(status, \'\')) IN (?, ?)',
                            [
                                'OPEN',
                                'ACTIVE',
                            ]
                        )
                        ->orWhere(function ($subQuery) {
                            $subQuery
                                ->where(function ($statusQuery) {
                                    $statusQuery
                                        ->whereNull('status')
                                        ->orWhere(
                                            'status',
                                            ''
                                        );
                                })
                                ->whereNull(
                                    'resolved_at'
                                );
                        });
                });

        /*
         * =====================================================
         * ACTIVE FINDINGS COUNT
         * =====================================================
         */

        $activeStats =
            (clone $activeQuery)
                ->selectRaw(
                    "
                    COUNT(*) AS open_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(severity, '')) = 'CRITICAL'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS open_critical,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(severity, '')) = 'HIGH'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS open_high,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(severity, '')) = 'MEDIUM'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS open_medium,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN UPPER(COALESCE(severity, '')) = 'LOW'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS open_low
                    "
                )
                ->first();

        $openFindings =
            (int) ($activeStats?->open_findings ?? 0);

        $openCritical =
            (int) ($activeStats?->open_critical ?? 0);

        $openHigh =
            (int) ($activeStats?->open_high ?? 0);

        $openMedium =
            (int) ($activeStats?->open_medium ?? 0);

        $openLow =
            (int) ($activeStats?->open_low ?? 0);

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
            (clone $teamAssessmentQuery)
                ->with('databaseConnection')
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
            (clone $teamFindingQuery)
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
            (clone $teamFindingQuery)
                ->latest('created_at')
                ->limit(15)
                ->get();

        /*
         * =====================================================
         * RISK TREND
         * =====================================================
         */

        $riskTrend =
            (clone $teamAssessmentQuery)
                ->whereBetween(
                    'scanned_at',
                    [
                        $startDate,
                        $endDate,
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

        $assessmentStats =
            (clone $teamAssessmentQuery)
                ->selectRaw(
                    '
                    COUNT(*) AS assessment_count,
                    AVG(score) AS average_score,
                    MAX(score) AS best_score,
                    MIN(score) AS worst_score
                    '
                )
                ->first();

        $assessmentCount =
            (int) (
                $assessmentStats?->assessment_count ?? 0
            );

        $averageScore =
            $assessmentStats?->average_score !== null
                ? round(
                    (float) $assessmentStats->average_score,
                    1
                )
                : 0;

        $bestScore =
            $assessmentStats?->best_score !== null
                ? (int) $assessmentStats->best_score
                : 0;

        $worstScore =
            $assessmentStats?->worst_score !== null
                ? (int) $assessmentStats->worst_score
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
