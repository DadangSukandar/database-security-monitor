<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SecurityIncidentReportService
{
    /** @return array<string, mixed> */
    public function build(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $incidents = $this->incidentQuery(
            $teamId,
            $start,
            $end
        )
            ->with('assignedTo:id,name')
            ->get([
                'id',
                'severity',
                'status',
                'assigned_to_user_id',
                'opened_at',
                'acknowledged_at',
                'resolved_at',
                'closed_at',
            ]);

        $reportQuery = $this->incidentQuery(
            $teamId,
            $start,
            $end
        );

        $totalIncidents = (clone $reportQuery)->count();

        $activeIncidents = (clone $reportQuery)
            ->where('status', '!=', 'CLOSED')
            ->count();

        $closedIncidents = (clone $reportQuery)
            ->where('status', 'CLOSED')
            ->count();

        $unassignedIncidents = (clone $reportQuery)
            ->whereNull('assigned_to_user_id')
            ->count();

        $acknowledgedCount = (clone $reportQuery)
            ->whereNotNull('acknowledged_at')
            ->count();

        $slaMet = (clone $reportQuery)
            ->whereNotNull('acknowledged_at')
            ->whereResponseSlaStatus('MET')
            ->count();

        $slaBreached = (clone $reportQuery)
            ->whereNotNull('acknowledged_at')
            ->whereResponseSlaStatus('BREACHED')
            ->count();

        $acknowledged = $incidents->whereNotNull('acknowledged_at');
        $resolved = $incidents->whereNotNull('resolved_at');

        return [
            'summary' => [
                'total' => $totalIncidents,
                'active' => $activeIncidents,
                'closed' => $closedIncidents,
                'unassigned' => $unassignedIncidents,
            ],
            'status_breakdown' => $this->countBy($incidents, 'status', [
                'OPEN',
                'ACKNOWLEDGED',
                'INVESTIGATING',
                'CONTAINED',
                'RESOLVED',
                'CLOSED',
            ]),
            'severity_breakdown' => $this->countBy($incidents, 'severity', [
                'CRITICAL',
                'HIGH',
                'MEDIUM',
                'LOW',
            ]),
            'priority_breakdown' => $this->priorityBreakdown(
                $teamId,
                $start,
                $end
            ),
            'assignment_breakdown' => $this->assignmentBreakdown($incidents),
            'sla' => [
                'acknowledged' => $acknowledgedCount,
                'met' => $slaMet,
                'breached' => $slaBreached,
                'met_rate' => $acknowledgedCount > 0
                    ? round(($slaMet / $acknowledgedCount) * 100, 1)
                    : null,

                'average_acknowledgement_minutes' => $this->averageMinutes(
                    $acknowledged,
                    'acknowledged_at'
                ),

                'average_resolution_minutes' => $this->averageMinutes(
                    $resolved,
                    'resolved_at'
                ),
            ],
            'trends' => $this->trends(
                $teamId,
                $start,
                $end
            ),

            'audit_summary' => $this->auditSummary(
                $teamId,
                $start,
                $end
            ),
        ];
    }

    public function auditQuery(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): Builder {
        return SecurityIncidentHistory::query()
            ->whereHas(
                'incident',
                fn (Builder $query) => $query->forTeam($teamId)
            )
            ->with([
                'incident:id,team_id,incident_number,title',
                'user:id,name,email',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->latest('created_at')
            ->latest('id');
    }

    private function incidentQuery(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): Builder {
        return SecurityIncident::query()
            ->forTeam($teamId)
            ->whereBetween('opened_at', [$start, $end]);
    }

    /**
     * @param  Collection<int, SecurityIncident>  $incidents
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    private function countBy(Collection $incidents, string $field, array $keys): array
    {
        $counts = array_fill_keys($keys, 0);

        foreach ($incidents as $incident) {
            $value = strtoupper((string) $incident->{$field});

            if (array_key_exists($value, $counts)) {
                $counts[$value]++;
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, SecurityIncident>  $incidents
     * @return array<string, int>
     */
    /**
     * @return array<string, int>
     */
    private function priorityBreakdown(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $query = $this->incidentQuery(
            $teamId,
            $start,
            $end
        );

        return [
            'P1' => (clone $query)
                ->whereTriagePriority('P1')
                ->count(),

            'P2' => (clone $query)
                ->whereTriagePriority('P2')
                ->count(),

            'P3' => (clone $query)
                ->whereTriagePriority('P3')
                ->count(),

            'P4' => (clone $query)
                ->whereTriagePriority('P4')
                ->count(),

            'NONE' => (clone $query)
                ->whereTriagePriority('NONE')
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, SecurityIncident>  $incidents
     * @return array{unassigned: int, assigned: int, by_pic: array<int, array{name: string, count: int}>}
     */
    private function assignmentBreakdown(Collection $incidents): array
    {
        $byPic = $incidents
            ->filter(fn (SecurityIncident $incident): bool => $incident->assigned_to_user_id !== null)
            ->groupBy('assigned_to_user_id')
            ->map(function (Collection $assignedIncidents): array {
                /** @var SecurityIncident $incident */
                $incident = $assignedIncidents->first();

                return [
                    'name' => $incident->assignedTo?->name ?? 'Deleted user',
                    'count' => $assignedIncidents->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $unassigned = $incidents->whereNull('assigned_to_user_id')->count();

        return [
            'unassigned' => $unassigned,
            'assigned' => $incidents->count() - $unassigned,
            'by_pic' => $byPic,
        ];
    }

    /** @param Collection<int, SecurityIncident> $incidents */
    private function averageMinutes(Collection $incidents, string $endField): ?float
    {
        if ($incidents->isEmpty()) {
            return null;
        }

        return round(
            (float) $incidents->average(
                fn (SecurityIncident $incident): float => $incident->opened_at->diffInMinutes(
                    $incident->{$endField}
                )
            ),
            1
        );
    }

    /** @return list<array{date: string, opened: int, resolved: int, closed: int}> */
    private function trends(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $opened = $this->dailyIncidentCounts(
            $teamId,
            'opened_at',
            $start,
            $end
        );

        $resolved = $this->dailyIncidentCounts(
            $teamId,
            'resolved_at',
            $start,
            $end
        );

        $closed = $this->dailyIncidentCounts(
            $teamId,
            'closed_at',
            $start,
            $end
        );

        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $date = $cursor->toDateString();

            $rows[] = [
                'date' => $date,
                'opened' => (int) ($opened[$date] ?? 0),
                'resolved' => (int) ($resolved[$date] ?? 0),
                'closed' => (int) ($closed[$date] ?? 0),
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    private function dailyIncidentCounts(
        int $teamId,
        string $column,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $driver = SecurityIncident::query()
            ->getModel()
            ->getConnection()
            ->getDriverName();

        $dateExpression = match ($driver) {
            'mysql', 'mariadb' => "DATE({$column})",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM-DD')",
            default => "DATE({$column})",
        };

        return SecurityIncident::query()
            ->forTeam($teamId)
            ->whereBetween($column, [$start, $end])
            ->selectRaw(
                "{$dateExpression} as event_date, COUNT(*) as aggregate"
            )
            ->groupByRaw($dateExpression)
            ->pluck('aggregate', 'event_date')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /** @return array<string, int> */
    private function auditSummary(
        int $teamId,
        CarbonInterface $start,
        CarbonInterface $end
    ): array {
        $actionCounts = SecurityIncidentHistory::query()
            ->whereHas(
                'incident',
                fn (Builder $query) => $query->forTeam($teamId)
            )
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw(
                'UPPER(action) as action_key, COUNT(*) as aggregate'
            )
            ->groupByRaw('UPPER(action)')
            ->pluck('aggregate', 'action_key');

        $counts = [
            'total' => 0,
            'lifecycle' => 0,
            'ownership' => 0,
            'investigation' => 0,
            'activity' => 0,
        ];

        foreach ($actionCounts as $action => $count) {
            $count = (int) $count;

            $counts['total'] += $count;

            $category = match ((string) $action) {
                'ACKNOWLEDGE',
                'INVESTIGATE',
                'CONTAIN',
                'RESOLVE',
                'CLOSE' => 'lifecycle',

                'ASSIGN',
                'REASSIGN',
                'UNASSIGN' => 'ownership',

                'INVESTIGATION_NOTE' => 'investigation',

                default => 'activity',
            };

            $counts[$category] += $count;
        }

        return $counts;
    }
}
