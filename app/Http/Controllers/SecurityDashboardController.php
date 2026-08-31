<?php

namespace App\Http\Controllers;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityDashboardController extends Controller
{
    /**
     * =========================================================
     * SECURITY DASHBOARD
     * =========================================================
     */
    public function index(): View
    {
        /*
         * =====================================================
         * LATEST ASSESSMENT
         * =====================================================
         */

        $latestAssessment = VulnerabilityAssessment::query()
            ->latest('id')
            ->first();

        $totalAssessments = VulnerabilityAssessment::count();

        /*
         * =====================================================
         * DEFAULT FINDING VALUES
         * =====================================================
         */

        $totalFindings = 0;

        $critical = 0;
        $high = 0;
        $medium = 0;
        $low = 0;

        $openFindings = 0;
        $resolvedFindings = 0;
        $ignoredFindings = 0;

        $securityScore = null;
        $scoreStatus = 'NO ASSESSMENT';

        $recentFindings = collect();
        $databaseFindings = collect();
        $categoryFindings = collect();

        /*
         * =====================================================
         * ASSESSMENT HISTORY
         * =====================================================
         */

        $assessmentHistory = VulnerabilityAssessment::query()
            ->latest('id')
            ->limit(10)
            ->get();

        /*
         * =====================================================
         * LATEST ASSESSMENT DATA
         * =====================================================
         */

        if ($latestAssessment) {

            /*
             * =================================================
             * SECURITY SCORE
             * =================================================
             */

            $securityScore = $latestAssessment->score;

            /*
             * =================================================
             * SCORE STATUS
             * =================================================
             */

            if ($securityScore === null) {

                $scoreStatus = 'NO SCORE';

            } elseif ($securityScore >= 90) {

                $scoreStatus = 'EXCELLENT';

            } elseif ($securityScore >= 75) {

                $scoreStatus = 'GOOD';

            } elseif ($securityScore >= 50) {

                $scoreStatus = 'NEEDS IMPROVEMENT';

            } elseif ($securityScore >= 25) {

                $scoreStatus = 'HIGH RISK';

            } else {

                $scoreStatus = 'CRITICAL RISK';
            }

            /*
             * =================================================
             * FINDINGS MILIK ASSESSMENT TERBARU
             * =================================================
             */

            $findingQuery = VulnerabilityFinding::query()
                ->where(
                    'vulnerability_assessment_id',
                    $latestAssessment->id
                );

            /*
             * =================================================
             * TOTAL FINDINGS
             * =================================================
             */

            $totalFindings =
                (clone $findingQuery)
                    ->count();

            /*
             * =================================================
             * FINDING STATUS
             * =================================================
             */

            $openFindings =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->count();

            $resolvedFindings =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'RESOLVED'
                    )
                    ->count();

            $ignoredFindings =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'IGNORED'
                    )
                    ->count();

            /*
             * =================================================
             * ACTIVE SEVERITY
             * =================================================
             */

            $critical =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->where(
                        'severity',
                        'CRITICAL'
                    )
                    ->count();

            $high =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->where(
                        'severity',
                        'HIGH'
                    )
                    ->count();

            $medium =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->where(
                        'severity',
                        'MEDIUM'
                    )
                    ->count();

            $low =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->where(
                        'severity',
                        'LOW'
                    )
                    ->count();

            /*
             * =================================================
             * RECENT FINDINGS
             * =================================================
             */

            $recentFindings =
                (clone $findingQuery)
                    ->latest('id')
                    ->limit(8)
                    ->get();

            /*
             * =================================================
             * FINDINGS BY DATABASE
             * =================================================
             */

            $databaseFindings =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->whereNotNull(
                        'database_name'
                    )
                    ->where(
                        'database_name',
                        '!=',
                        ''
                    )
                    ->select(
                        'database_name',
                        DB::raw(
                            'COUNT(*) as total'
                        )
                    )
                    ->groupBy(
                        'database_name'
                    )
                    ->orderByDesc(
                        'total'
                    )
                    ->limit(10)
                    ->get();

            /*
             * =================================================
             * FINDINGS BY CATEGORY
             * =================================================
             */

            $categoryFindings =
                (clone $findingQuery)
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->whereNotNull(
                        'category'
                    )
                    ->where(
                        'category',
                        '!=',
                        ''
                    )
                    ->select(
                        'category',
                        DB::raw(
                            'COUNT(*) as total'
                        )
                    )
                    ->groupBy(
                        'category'
                    )
                    ->orderByDesc(
                        'total'
                    )
                    ->limit(10)
                    ->get();
        }

        /*
         * =====================================================
         * SECURITY ALERTS
         * =====================================================
         */

        $alertQuery = SecurityAlert::query()
            ->canonical()
            ->where(
                'alert_type',
                'VULNERABILITY'
            );

        /*
         * Total seluruh vulnerability alerts.
         */

        $totalAlerts =
            (clone $alertQuery)
                ->count();

        /*
         * Open alerts.
         */

        $totalOpenAlerts =
            (clone $alertQuery)
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        /*
         * Critical open alerts.
         */

        $criticalAlerts =
            (clone $alertQuery)
                ->where(
                    'status',
                    'OPEN'
                )
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->count();

        /*
         * High open alerts.
         */

        $highAlerts =
            (clone $alertQuery)
                ->where(
                    'status',
                    'OPEN'
                )
                ->where(
                    'severity',
                    'HIGH'
                )
                ->count();

        /*
         * Resolved alerts.
         */

        $resolvedAlerts =
            (clone $alertQuery)
                ->where(
                    'status',
                    'RESOLVED'
                )
                ->count();

        /*
         * Recent alerts.
         */

        $recentAlerts =
            (clone $alertQuery)
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
                ->latest('detected_at')
                ->latest('id')
                ->limit(8)
                ->get();

        /*
         * =====================================================
         * ALERT LIFECYCLE ANALYTICS
         * =====================================================
         */

        $acknowledgedAlerts =
            (clone $alertQuery)
                ->whereNotNull('acknowledged_at')
                ->count();

        $acknowledgementRate = $totalAlerts > 0
            ? round(($acknowledgedAlerts / $totalAlerts) * 100, 1)
            : 0.0;

        $resolutionRate = $totalAlerts > 0
            ? round(($resolvedAlerts / $totalAlerts) * 100, 1)
            : 0.0;

        $acknowledgementDurations =
            (clone $alertQuery)
                ->whereNotNull('detected_at')
                ->whereNotNull('acknowledged_at')
                ->get(['detected_at', 'acknowledged_at'])
                ->map(fn (SecurityAlert $alert): float => $alert->detected_at->diffInMinutes(
                    $alert->acknowledged_at
                ));

        $resolutionDurations =
            (clone $alertQuery)
                ->whereNotNull('detected_at')
                ->whereNotNull('resolved_at')
                ->get(['detected_at', 'resolved_at'])
                ->map(fn (SecurityAlert $alert): float => $alert->detected_at->diffInMinutes(
                    $alert->resolved_at
                ));

        $averageAcknowledgementMinutes = $acknowledgementDurations->isNotEmpty()
            ? round($acknowledgementDurations->average(), 1)
            : null;

        $averageResolutionMinutes = $resolutionDurations->isNotEmpty()
            ? round($resolutionDurations->average(), 1)
            : null;

        $recentAlertActivity = SecurityAlertHistory::query()
            ->with('alert')
            ->whereHas('alert', fn ($query) => $query->canonical()->where('alert_type', 'VULNERABILITY'))
            ->latest()
            ->limit(8)
            ->get();

        $activeAlertsForSla = (clone $alertQuery)
            ->whereIn('status', ['OPEN', 'ACKNOWLEDGED', 'INVESTIGATING'])
            ->get();

        $breachedSlaAlerts = $activeAlertsForSla
            ->filter(fn (SecurityAlert $alert): bool => $alert->responseSlaStatus() === 'BREACHED')
            ->count();

        $dueSoonSlaAlerts = $activeAlertsForSla
            ->filter(fn (SecurityAlert $alert): bool => $alert->responseSlaStatus() === 'DUE_SOON')
            ->count();

        /*
         * =====================================================
         * ALERTS UNTUK ASSESSMENT / DATABASE TERBARU
         * =====================================================
         */

        $latestDatabaseAlerts = collect();

        if ($latestAssessment) {

            $latestDatabaseAlerts =
                SecurityAlert::query()
                    ->canonical()
                    ->where(
                        'alert_type',
                        'VULNERABILITY'
                    )
                    ->where(
                        'database_name',
                        $latestAssessment->database_name
                    )
                    ->where(
                        'status',
                        'OPEN'
                    )
                    ->latest('detected_at')
                    ->limit(10)
                    ->get();
        }

        /*
         * =====================================================
         * ALERT SEVERITY DISTRIBUTION
         * =====================================================
         */

        $alertSeverityDistribution =
            SecurityAlert::query()
                ->canonical()
                ->where(
                    'alert_type',
                    'VULNERABILITY'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->select(
                    'severity',
                    DB::raw(
                        'COUNT(*) as total'
                    )
                )
                ->groupBy(
                    'severity'
                )
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
                ->get();

        /*
         * =====================================================
         * SECURITY OVERVIEW
         * =====================================================
         */

        $securityOverview = [

            'score' => $securityScore,

            'score_status' => $scoreStatus,

            'assessment_id' => $latestAssessment?->id,

            'database_name' => $latestAssessment?->database_name,

            'findings' => [

                'total' => $totalFindings,

                'open' => $openFindings,

                'resolved' => $resolvedFindings,

                'ignored' => $ignoredFindings,

                'critical' => $critical,

                'high' => $high,

                'medium' => $medium,

                'low' => $low,
            ],

            'alerts' => [

                'total' => $totalAlerts,

                'open' => $totalOpenAlerts,

                'critical' => $criticalAlerts,

                'high' => $highAlerts,

                'resolved' => $resolvedAlerts,
            ],
        ];

        /*
         * =====================================================
         * RETURN VIEW
         * =====================================================
         */

        return view(
            'security-dashboard.index',
            compact(

                /*
                 * Assessment
                 */
                'latestAssessment',
                'totalAssessments',
                'assessmentHistory',

                /*
                 * Score
                 */
                'securityScore',
                'scoreStatus',

                /*
                 * Findings
                 */
                'totalFindings',
                'critical',
                'high',
                'medium',
                'low',
                'openFindings',
                'resolvedFindings',
                'ignoredFindings',
                'recentFindings',
                'databaseFindings',
                'categoryFindings',

                /*
                 * Alerts
                 */
                'totalAlerts',
                'totalOpenAlerts',
                'criticalAlerts',
                'highAlerts',
                'resolvedAlerts',
                'recentAlerts',
                'acknowledgedAlerts',
                'acknowledgementRate',
                'resolutionRate',
                'averageAcknowledgementMinutes',
                'averageResolutionMinutes',
                'recentAlertActivity',
                'breachedSlaAlerts',
                'dueSoonSlaAlerts',
                'latestDatabaseAlerts',
                'alertSeverityDistribution',

                /*
                 * Overview
                 */
                'securityOverview'
            )
        );
    }
}
