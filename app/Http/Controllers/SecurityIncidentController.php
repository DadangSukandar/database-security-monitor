<?php

namespace App\Http\Controllers;

use App\Models\SecurityIncident;
use App\Services\SecurityIncidentAssignmentService;
use App\Services\SecurityIncidentInvestigationService;
use App\Services\SecurityIncidentLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecurityIncidentController extends Controller
{
    private function ensureIncidentBelongsToCurrentTeam(
        Request $request,
        SecurityIncident $incident
    ): void {
        $teamId = $request->user()?->current_team_id;

        abort_if(
            $teamId === null ||
            (int) $incident->team_id !== (int) $teamId,
            404
        );
    }

    public function index(Request $request): View
    {

        $currentTeam = $request->user()->currentTeam;

        $teamId = (int) $currentTeam->id;

        $teamMembers = $currentTeam
            ->members()
            ->orderBy('name')
            ->get();

        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                'string',
                'in:OPEN,ACKNOWLEDGED,INVESTIGATING,CONTAINED,RESOLVED,CLOSED',
            ],
            'severity' => [
                'nullable',
                'string',
                'in:CRITICAL,HIGH,MEDIUM,LOW',
            ],
            'priority' => [
                'nullable',
                'string',
                'in:P1,P2,P3,P4,NONE',
            ],
            'sla' => [
                'nullable',
                'string',
                'in:BREACHED,DUE_SOON,ON_TRACK,MET',
            ],
            'pic' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? null;
        $severity = $filters['severity'] ?? null;
        $priority = $filters['priority'] ?? null;
        $sla = $filters['sla'] ?? null;
        $pic = $filters['pic'] ?? null;

        $currentTeam = $request->user()->currentTeam;

        $incidentQuery = SecurityIncident::query()
            ->forTeam($teamId);

        $teamMembers = $currentTeam
            ? $currentTeam
                ->members()
                ->orderBy('name')
                ->get()
            : collect();

        if (
            $pic !== null &&
            $pic !== '' &&
            $pic !== 'unassigned'
        ) {
            if (! ctype_digit($pic)) {
                throw ValidationException::withMessages([
                    'pic' => 'PIC filter tidak valid.',
                ]);
            }

            $picUserId = (int) $pic;

            $isCurrentTeamMember = $teamMembers->contains(
                fn ($member) => (int) $member->id === $picUserId
            );

            if (! $isCurrentTeamMember) {
                throw ValidationException::withMessages([
                    'pic' => 'PIC harus merupakan anggota current team.',
                ]);
            }
        }

        $incidentMetrics = [
            'active' => (clone $incidentQuery)
                ->where('status', '!=', 'CLOSED')
                ->count(),

            'open' => (clone $incidentQuery)
                ->where('status', 'OPEN')
                ->count(),

            'investigating' => (clone $incidentQuery)
                ->where('status', 'INVESTIGATING')
                ->count(),

            'critical_high' => (clone $incidentQuery)
                ->where('status', '!=', 'CLOSED')
                ->whereIn('severity', [
                    'CRITICAL',
                    'HIGH',
                ])
                ->count(),

            'unassigned' => (clone $incidentQuery)
                ->where('status', '!=', 'CLOSED')
                ->whereNull('assigned_to_user_id')
                ->count(),
        ];

        $oldestActiveIncident = (clone $incidentQuery)
            ->where('status', '!=', 'CLOSED')
            ->whereNotNull('opened_at')
            ->oldest('opened_at')
            ->first(['id', 'opened_at', 'closed_at']);

        $incidentAgingMetrics = [
            'oldest_active' => $oldestActiveIncident?->ageLabel(),
        ];

        $incidentSlaMetrics = [
            'breached' => (clone $incidentQuery)
                ->where('status', '!=', 'CLOSED')
                ->whereResponseSlaStatus('BREACHED')
                ->count(),

            'due_soon' => (clone $incidentQuery)
                ->where('status', '!=', 'CLOSED')
                ->whereResponseSlaStatus('DUE_SOON')
                ->count(),
        ];

        $acknowledgedIncidents = (clone $incidentQuery)
            ->whereNotNull('opened_at')
            ->whereNotNull('acknowledged_at')
            ->get([
                'id',
                'severity',
                'status',
                'opened_at',
                'acknowledged_at',
            ]);

        $resolvedIncidents = (clone $incidentQuery)
            ->whereNotNull('opened_at')
            ->whereNotNull('resolved_at')
            ->get([
                'id',
                'opened_at',
                'resolved_at',
            ]);

        $acknowledgementDurations = $acknowledgedIncidents
            ->map(
                fn (SecurityIncident $incident): float => $incident->opened_at->diffInMinutes(
                    $incident->acknowledged_at
                )
            );

        $resolutionDurations = $resolvedIncidents
            ->map(
                fn (SecurityIncident $incident): float => $incident->opened_at->diffInMinutes(
                    $incident->resolved_at
                )
            );

        $acknowledgementSlaMet = (clone $incidentQuery)
            ->whereNotNull('opened_at')
            ->whereNotNull('acknowledged_at')
            ->whereResponseSlaStatus('MET')
            ->count();

        $acknowledgementSlaBreached = (clone $incidentQuery)
            ->whereNotNull('opened_at')
            ->whereNotNull('acknowledged_at')
            ->whereResponseSlaStatus('BREACHED')
            ->count();

        $acknowledgedCount = (clone $incidentQuery)
            ->whereNotNull('opened_at')
            ->whereNotNull('acknowledged_at')
            ->count();

        $incidentResolutionMetrics = [
            'average_acknowledgement_minutes' => $acknowledgementDurations->isNotEmpty()
                    ? round($acknowledgementDurations->average(), 1)
                    : null,

            'average_resolution_minutes' => $resolutionDurations->isNotEmpty()
                    ? round($resolutionDurations->average(), 1)
                    : null,

            'acknowledgement_sla_met' => $acknowledgementSlaMet,

            'acknowledgement_sla_breached' => $acknowledgementSlaBreached,

            'acknowledgement_sla_met_rate' => $acknowledgedCount > 0
                    ? round(
                        ($acknowledgementSlaMet / $acknowledgedCount) * 100,
                        1
                    )
                    : null,
        ];

        $incidents = SecurityIncident::query()
            ->forTeam($teamId)
            ->with([
                'securityAlert',
                'assignedTo',
                'createdBy',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'incident_number',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'title',
                                'like',
                                '%'.$search.'%'
                            );
                    });
                }
            )
            ->when(
                $status !== null,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $severity !== null,
                fn ($query) => $query->where(
                    'severity',
                    $severity
                )
            )
            ->when(
                $priority !== null,
                fn ($query) => $query->whereTriagePriority(
                    $priority
                )
            )
            ->when(
                $sla !== null,
                fn ($query) => $query->whereResponseSlaStatus(
                    $sla
                )
            )
            ->when(
                $pic === 'unassigned',
                fn ($query) => $query->whereNull(
                    'assigned_to_user_id'
                )
            )
            ->when(
                $pic !== null
                    && $pic !== ''
                    && $pic !== 'unassigned',
                fn ($query) => $query->where(
                    'assigned_to_user_id',
                    (int) $pic
                )
            )
            ->orderByTriagePriority()
            ->paginate(20)
            ->withQueryString();

        return view(
            'security-incidents.index',
            compact(
                'incidents',
                'teamMembers',
                'incidentMetrics',
                'incidentAgingMetrics',
                'incidentSlaMetrics',
                'incidentResolutionMetrics',
            )
        );
    }

    public function show(
        Request $request,
        SecurityIncident $incident
    ): View {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        $incident->load([
            'securityAlert',
            'assignedTo',
            'createdBy',
            'histories.user',
        ]);

        $currentTeam = $request->user()->currentTeam;

        $teamMembers = $currentTeam
            ->members()
            ->orderBy('name')
            ->get();

        return view(
            'security-incidents.show',
            compact(
                'incident',
                'teamMembers'
            )
        );
    }

    public function acknowledge(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        try {
            $lifecycle->acknowledge(
                $incident,
                $request->user()->id
            );

            return back()->with(
                'success',
                'Incident berhasil di-acknowledge.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function investigate(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {

        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );
        try {
            $lifecycle->investigate(
                $incident,
                $request->user()->id
            );

            return back()->with(
                'success',
                'Investigasi incident berhasil dimulai.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function contain(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        try {
            $lifecycle->contain(
                $incident,
                $request->user()->id
            );

            return back()->with(
                'success',
                'Incident berhasil di-contain.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function resolve(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        $validated = $request->validate([
            'resolution_note' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $lifecycle->resolve(
                $incident,
                $validated['resolution_note'],
                $request->user()->id
            );

            return back()->with(
                'success',
                'Incident berhasil diselesaikan.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function close(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        try {
            $lifecycle->close(
                $incident,
                $request->user()->id
            );

            return back()->with(
                'success',
                'Incident berhasil ditutup.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function assign(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentAssignmentService $assignment
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );
        $validated = $request->validate([
            'assigned_to_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $actor = $request->user();
        $currentTeam = $actor->currentTeam;

        if ($currentTeam === null) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'Anda tidak memiliki current team.',
            ]);
        }

        $assignee = $currentTeam
            ->members()
            ->where(
                'users.id',
                $validated['assigned_to_user_id']
            )
            ->first();

        if ($assignee === null) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'PIC harus merupakan anggota current team.',
            ]);
        }

        try {
            $assignment->assign(
                $incident,
                $assignee,
                $actor->id
            );

            return back()->with(
                'success',
                'PIC security incident berhasil diperbarui.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => 'Gagal assign security incident: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function unassign(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentAssignmentService $assignment
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );

        try {
            $assignment->unassign(
                $incident,
                $request->user()->id
            );

            return back()->with(
                'success',
                'PIC security incident berhasil dilepas.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => 'Gagal unassign security incident: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function addInvestigationNote(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentInvestigationService $investigation
    ): RedirectResponse {
        $this->ensureIncidentBelongsToCurrentTeam(
            $request,
            $incident
        );
        $validated = $request->validate([
            'note' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $investigation->addNote(
                $incident,
                $validated['note'],
                $request->user()->id
            );

            return back()->with(
                'success',
                'Investigation note berhasil ditambahkan.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'incident' => 'Gagal menambahkan investigation note: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }
}
