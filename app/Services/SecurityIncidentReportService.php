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
    public function build(CarbonInterface $start, CarbonInterface $end): array
    {
        $incidents = $this->incidentQuery($start, $end)
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

        $acknowledged = $incidents->whereNotNull('acknowledged_at');
        $resolved = $incidents->whereNotNull('resolved_at');
        $slaMet = $acknowledged->filter(
            fn (SecurityIncident $incident): bool => $incident->responseSlaStatus() === 'MET'
        )->count();
        $slaBreached = $acknowledged->filter(
            fn (SecurityIncident $incident): bool => $incident->responseSlaStatus() === 'BREACHED'
        )->count();

        return [
            'summary' => [
                'total' => $incidents->count(),
                'active' => $incidents->where('status', '!=', 'CLOSED')->count(),
                'closed' => $incidents->where('status', 'CLOSED')->count(),
                'unassigned' => $incidents->whereNull('assigned_to_user_id')->count(),
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
            'priority_breakdown' => $this->priorityBreakdown($incidents),
            'assignment_breakdown' => $this->assignmentBreakdown($incidents),
            'sla' => [
                'acknowledged' => $acknowledged->count(),
                'met' => $slaMet,
                'breached' => $slaBreached,
                'met_rate' => $acknowledged->isNotEmpty()
                    ? round(($slaMet / $acknowledged->count()) * 100, 1)
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
            'trends' => $this->trends($start, $end),
            'audit_summary' => $this->auditSummary($start, $end),
        ];
    }

    public function auditQuery(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return SecurityIncidentHistory::query()
            ->with([
                'incident:id,incident_number,title',
                'user:id,name,email',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->latest('created_at')
            ->latest('id');
    }

    private function incidentQuery(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return SecurityIncident::query()
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
    private function priorityBreakdown(Collection $incidents): array
    {
        $counts = array_fill_keys(['P1', 'P2', 'P3', 'P4', 'NONE'], 0);

        foreach ($incidents as $incident) {
            $counts[$incident->triagePriority()]++;
        }

        return $counts;
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
    private function trends(CarbonInterface $start, CarbonInterface $end): array
    {
        $opened = SecurityIncident::query()
            ->whereBetween('opened_at', [$start, $end])
            ->get(['opened_at'])
            ->countBy(fn (SecurityIncident $incident): string => $incident->opened_at->toDateString());

        $resolved = SecurityIncident::query()
            ->whereBetween('resolved_at', [$start, $end])
            ->get(['resolved_at'])
            ->countBy(fn (SecurityIncident $incident): string => $incident->resolved_at->toDateString());

        $closed = SecurityIncident::query()
            ->whereBetween('closed_at', [$start, $end])
            ->get(['closed_at'])
            ->countBy(fn (SecurityIncident $incident): string => $incident->closed_at->toDateString());

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

    /** @return array<string, int> */
    private function auditSummary(CarbonInterface $start, CarbonInterface $end): array
    {
        $histories = SecurityIncidentHistory::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['action']);

        $counts = [
            'total' => $histories->count(),
            'lifecycle' => 0,
            'ownership' => 0,
            'investigation' => 0,
            'activity' => 0,
        ];

        foreach ($histories as $history) {
            $key = strtolower($history->activityCategory());
            $counts[$key]++;
        }

        return $counts;
    }
}
