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
    public function index(Request $request): View
    {
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
            'pic' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? null;
        $severity = $filters['severity'] ?? null;
        $pic = $filters['pic'] ?? null;

        $currentTeam = $request->user()->currentTeam;

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
            'active' => SecurityIncident::query()
                ->where('status', '!=', 'CLOSED')
                ->count(),

            'open' => SecurityIncident::query()
                ->where('status', 'OPEN')
                ->count(),

            'investigating' => SecurityIncident::query()
                ->where('status', 'INVESTIGATING')
                ->count(),

            'critical_high' => SecurityIncident::query()
                ->where('status', '!=', 'CLOSED')
                ->whereIn('severity', [
                    'CRITICAL',
                    'HIGH',
                ])
                ->count(),

            'unassigned' => SecurityIncident::query()
                ->where('status', '!=', 'CLOSED')
                ->whereNull('assigned_to_user_id')
                ->count(),
        ];

        $oldestActiveIncident = SecurityIncident::query()
            ->where('status', '!=', 'CLOSED')
            ->whereNotNull('opened_at')
            ->oldest('opened_at')
            ->first();

        $incidentAgingMetrics = [
            'oldest_active' => $oldestActiveIncident?->ageLabel(),
        ];

        $activeIncidentsForSla = SecurityIncident::query()
            ->where('status', '!=', 'CLOSED')
            ->get();

        $incidentSlaMetrics = [
            'breached' => $activeIncidentsForSla
                ->filter(
                    fn (SecurityIncident $incident): bool => $incident->responseSlaStatus() === 'BREACHED'
                )
                ->count(),

            'due_soon' => $activeIncidentsForSla
                ->filter(
                    fn (SecurityIncident $incident): bool => $incident->responseSlaStatus() === 'DUE_SOON'
                )
                ->count(),
        ];

        $incidents = SecurityIncident::query()
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
            ->latest('opened_at')
            ->latest('id')
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
            )
        );
    }

    public function show(
        SecurityIncident $incident
    ): View {
        $incident->load([
            'securityAlert',
            'assignedTo',
            'createdBy',
            'histories.user',
        ]);

        $currentTeam = auth()->user()->currentTeam;

        $teamMembers = $currentTeam
            ? $currentTeam
                ->members()
                ->orderBy('name')
                ->get()
            : collect();

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
                'incident' => $e->getMessage(),
            ]);
        }
    }

    public function investigate(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
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
                'incident' => $e->getMessage(),
            ]);
        }
    }

    public function contain(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
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
                'incident' => $e->getMessage(),
            ]);
        }
    }

    public function resolve(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
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
                'incident' => $e->getMessage(),
            ]);
        }
    }

    public function close(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentLifecycleService $lifecycle
    ): RedirectResponse {
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
                'incident' => $e->getMessage(),
            ]);
        }
    }

    public function assign(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentAssignmentService $assignment
    ): RedirectResponse {
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
                    $e->getMessage(),
            ]);
        }
    }

    public function unassign(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentAssignmentService $assignment
    ): RedirectResponse {
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
                    $e->getMessage(),
            ]);
        }
    }

    public function addInvestigationNote(
        Request $request,
        SecurityIncident $incident,
        SecurityIncidentInvestigationService $investigation
    ): RedirectResponse {
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
                    $e->getMessage(),
            ]);
        }
    }
}
