<?php

namespace App\Http\Controllers;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\SecurityIncident;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityDashboardController extends Controller
{
    /**
     * =========================================================
     * SECURITY DASHBOARD
     * =========================================================
     */
    public function index(Request $request): View
    {
        $teamId = (int) $request->user()->current_team_id;
        $assessmentQuery = VulnerabilityAssessment::query()
            ->forTeam($teamId);
        /*
         * =====================================================
         * LATEST ASSESSMENT
         * =====================================================
         */

        $latestAssessment = (clone $assessmentQuery)
            ->latest('id')
            ->first();

        $totalAssessments = (clone $assessmentQuery)
            ->count();

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

        $assessmentHistory = (clone $assessmentQuery)
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
                ->forTeam($teamId)
                ->where(
                    'vulnerability_assessment_id',
                    $latestAssessment->id
                );

            /*
            * =================================================
            * FINDING AGGREGATE STATISTICS
            * =================================================
            */
            $findingStats =
                (clone $findingQuery)
                    ->selectRaw(
                        "
                        COUNT(*) AS total_findings,

                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                THEN 1
                                ELSE 0
                            END
                        ) AS open_findings,

                        SUM(
                            CASE
                                WHEN status = 'RESOLVED'
                                THEN 1
                                ELSE 0
                            END
                        ) AS resolved_findings,

                        SUM(
                            CASE
                                WHEN status = 'IGNORED'
                                THEN 1
                                ELSE 0
                            END
                        ) AS ignored_findings,

                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'CRITICAL'
                                THEN 1
                                ELSE 0
                            END
                        ) AS critical_findings,

                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'HIGH'
                                THEN 1
                                ELSE 0
                            END
                        ) AS high_findings,

                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'MEDIUM'
                                THEN 1
                                ELSE 0
                            END
                        ) AS medium_findings,

                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'LOW'
                                THEN 1
                                ELSE 0
                            END
                        ) AS low_findings
                        "
                    )
                    ->first();

            $totalFindings =
                (int) ($findingStats?->total_findings ?? 0);

            $openFindings =
                (int) ($findingStats?->open_findings ?? 0);

            $resolvedFindings =
                (int) ($findingStats?->resolved_findings ?? 0);

            $ignoredFindings =
                (int) ($findingStats?->ignored_findings ?? 0);

            $critical =
                (int) ($findingStats?->critical_findings ?? 0);

            $high =
                (int) ($findingStats?->high_findings ?? 0);

            $medium =
                (int) ($findingStats?->medium_findings ?? 0);

            $low =
                (int) ($findingStats?->low_findings ?? 0);

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
            ->forTeam($teamId)
            ->canonical()
            ->where(
                'alert_type',
                'VULNERABILITY'
            );

        /*
        * =====================================================
        * ALERT AGGREGATE STATISTICS
        * =====================================================
        */
        $alertStats =
            (clone $alertQuery)
                ->selectRaw(
                    "
                    COUNT(*) AS total_alerts,

                    SUM(
                        CASE
                            WHEN status = 'OPEN'
                            THEN 1
                            ELSE 0
                        END
                    ) AS total_open_alerts,

                    SUM(
                        CASE
                            WHEN status = 'OPEN'
                                AND severity = 'CRITICAL'
                            THEN 1
                            ELSE 0
                        END
                    ) AS critical_alerts,

                    SUM(
                        CASE
                            WHEN status = 'OPEN'
                                AND severity = 'HIGH'
                            THEN 1
                            ELSE 0
                        END
                    ) AS high_alerts,

                    SUM(
                        CASE
                            WHEN status = 'RESOLVED'
                            THEN 1
                            ELSE 0
                        END
                    ) AS resolved_alerts,

                    SUM(
                        CASE
                            WHEN acknowledged_at IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS acknowledged_alerts
                    "
                )
                ->first();

        $totalAlerts =
            (int) ($alertStats?->total_alerts ?? 0);

        $totalOpenAlerts =
            (int) ($alertStats?->total_open_alerts ?? 0);

        $criticalAlerts =
            (int) ($alertStats?->critical_alerts ?? 0);

        $highAlerts =
            (int) ($alertStats?->high_alerts ?? 0);

        $resolvedAlerts =
            (int) ($alertStats?->resolved_alerts ?? 0);

        $acknowledgedAlerts =
            (int) ($alertStats?->acknowledged_alerts ?? 0);

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

        $acknowledgementRate = $totalAlerts > 0
            ? round(($acknowledgedAlerts / $totalAlerts) * 100, 1)
            : 0.0;

        $resolutionRate = $totalAlerts > 0
            ? round(($resolvedAlerts / $totalAlerts) * 100, 1)
            : 0.0;

        $alertDriver = SecurityAlert::query()
            ->getModel()
            ->getConnection()
            ->getDriverName();

        $acknowledgementDurationExpression = match ($alertDriver) {
            'mysql', 'mariadb' => 'TIMESTAMPDIFF(SECOND, detected_at, acknowledged_at) / 60.0',

            'pgsql' => 'EXTRACT(EPOCH FROM (acknowledged_at - detected_at)) / 60.0',

            default => '(julianday(acknowledged_at) - julianday(detected_at)) * 1440.0',
        };

        $resolutionDurationExpression = match ($alertDriver) {
            'mysql', 'mariadb' => 'TIMESTAMPDIFF(SECOND, detected_at, resolved_at) / 60.0',

            'pgsql' => 'EXTRACT(EPOCH FROM (resolved_at - detected_at)) / 60.0',

            default => '(julianday(resolved_at) - julianday(detected_at)) * 1440.0',
        };

        $averageAcknowledgementMinutes = (clone $alertQuery)
            ->whereNotNull('detected_at')
            ->whereNotNull('acknowledged_at')
            ->selectRaw(
                "AVG({$acknowledgementDurationExpression}) as average_minutes"
            )
            ->value('average_minutes');

        $averageAcknowledgementMinutes =
            $averageAcknowledgementMinutes !== null
                ? round((float) $averageAcknowledgementMinutes, 1)
                : null;

        $averageResolutionMinutes = (clone $alertQuery)
            ->whereNotNull('detected_at')
            ->whereNotNull('resolved_at')
            ->selectRaw(
                "AVG({$resolutionDurationExpression}) as average_minutes"
            )
            ->value('average_minutes');

        $averageResolutionMinutes =
            $averageResolutionMinutes !== null
                ? round((float) $averageResolutionMinutes, 1)
                : null;

        $recentAlertActivity = SecurityAlertHistory::query()
            ->with('alert')
            ->whereHas(
                'alert',
                fn ($query) => $query
                    ->forTeam($teamId)
                    ->canonical()
                    ->where(
                        'alert_type',
                        'VULNERABILITY'
                    )
            )
            ->latest()
            ->limit(8)
            ->get();

        $slaEvaluatedAt =
            now();

        $activeAlertSlaQuery =
            (clone $alertQuery)
                ->whereIn(
                    'status',
                    [
                        'OPEN',
                        'ACKNOWLEDGED',
                        'INVESTIGATING',
                    ]
                );

        $slaStats =
            DB::query()
                ->selectSub(
                    (clone $activeAlertSlaQuery)
                        ->whereResponseSlaStatus(
                            'BREACHED',
                            $slaEvaluatedAt
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'breached_sla_alerts'
                )
                ->selectSub(
                    (clone $activeAlertSlaQuery)
                        ->whereResponseSlaStatus(
                            'DUE_SOON',
                            $slaEvaluatedAt
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'due_soon_sla_alerts'
                )
                ->first();

        $breachedSlaAlerts =
            (int) (
                $slaStats
                    ?->breached_sla_alerts
                ?? 0
            );

        $dueSoonSlaAlerts =
            (int) (
                $slaStats
                    ?->due_soon_sla_alerts
                ?? 0
            );

        /*
         * =====================================================
         * ALERTS UNTUK ASSESSMENT / DATABASE TERBARU
         * =====================================================
         */

        $latestDatabaseAlerts = collect();

        if ($latestAssessment) {

            $latestDatabaseAlerts =
                SecurityAlert::query()
                    ->forTeam($teamId)
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
                ->forTeam($teamId)
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
        * SECURITY INCIDENTS
        * =====================================================
        */

        $incidentQuery = SecurityIncident::query()
            ->forTeam($teamId);

        /*
        * =====================================================
        * INCIDENT AGGREGATE STATISTICS
        * =====================================================
        */
        $incidentStats =
            (clone $incidentQuery)
                ->selectRaw(
                    "
                    COUNT(*) AS total_incidents,

                    SUM(
                        CASE
                            WHEN status != 'CLOSED'
                            THEN 1
                            ELSE 0
                        END
                    ) AS active_incidents,

                    SUM(
                        CASE
                            WHEN status = 'CLOSED'
                            THEN 1
                            ELSE 0
                        END
                    ) AS closed_incidents,

                    SUM(
                        CASE
                            WHEN status != 'CLOSED'
                                AND severity = 'CRITICAL'
                            THEN 1
                            ELSE 0
                        END
                    ) AS critical_incidents,

                    SUM(
                        CASE
                            WHEN status != 'CLOSED'
                                AND severity = 'HIGH'
                            THEN 1
                            ELSE 0
                        END
                    ) AS high_incidents,

                    SUM(
                        CASE
                            WHEN status != 'CLOSED'
                                AND assigned_to_user_id IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS unassigned_incidents
                    "
                )
                ->first();

        $totalIncidents =
            (int) ($incidentStats?->total_incidents ?? 0);

        $activeIncidents =
            (int) ($incidentStats?->active_incidents ?? 0);

        $closedIncidents =
            (int) ($incidentStats?->closed_incidents ?? 0);

        $criticalIncidents =
            (int) ($incidentStats?->critical_incidents ?? 0);

        $highIncidents =
            (int) ($incidentStats?->high_incidents ?? 0);

        $unassignedIncidents =
            (int) ($incidentStats?->unassigned_incidents ?? 0);

        $activeIncidentMetricQuery =
            (clone $incidentQuery)
                ->where(
                    'status',
                    '!=',
                    'CLOSED'
                );

        $incidentOperationalStats =
            DB::query()
                ->selectSub(
                    (clone $activeIncidentMetricQuery)
                        ->whereResponseSlaStatus(
                            'BREACHED'
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'breached_incident_sla'
                )
                ->selectSub(
                    (clone $activeIncidentMetricQuery)
                        ->whereResponseSlaStatus(
                            'DUE_SOON'
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'due_soon_incident_sla'
                )
                ->selectSub(
                    (clone $activeIncidentMetricQuery)
                        ->whereTriagePriority(
                            'P1'
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'p1_incidents'
                )
                ->selectSub(
                    (clone $activeIncidentMetricQuery)
                        ->whereTriagePriority(
                            'P2'
                        )
                        ->selectRaw(
                            'COUNT(*)'
                        ),
                    'p2_incidents'
                )
                ->first();

        $breachedIncidentSla =
            (int) (
                $incidentOperationalStats
                    ?->breached_incident_sla
                ?? 0
            );

        $dueSoonIncidentSla =
            (int) (
                $incidentOperationalStats
                    ?->due_soon_incident_sla
                ?? 0
            );

        $p1Incidents =
            (int) (
                $incidentOperationalStats
                    ?->p1_incidents
                ?? 0
            );

        $p2Incidents =
            (int) (
                $incidentOperationalStats
                    ?->p2_incidents
                ?? 0
            );

        $recentIncidents = (clone $incidentQuery)
            ->with([
                'assignedTo',
                'securityAlert',
            ])
            ->latest('opened_at')
            ->latest('id')
            ->limit(5)
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

            'incidents' => [
                'total' => $totalIncidents,
                'active' => $activeIncidents,
                'closed' => $closedIncidents,
                'critical' => $criticalIncidents,
                'high' => $highIncidents,
                'unassigned' => $unassignedIncidents,
                'sla_breached' => $breachedIncidentSla,
                'sla_due_soon' => $dueSoonIncidentSla,
                'p1' => $p1Incidents,
                'p2' => $p2Incidents,
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
                * Incidents
                */
                'totalIncidents',
                'activeIncidents',
                'closedIncidents',
                'criticalIncidents',
                'highIncidents',
                'unassignedIncidents',
                'breachedIncidentSla',
                'dueSoonIncidentSla',
                'p1Incidents',
                'p2Incidents',
                'recentIncidents',

                /*
                 * Overview
                 */
                'securityOverview'
            )
        );
    }
}
