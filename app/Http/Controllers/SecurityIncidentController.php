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
    public function index(): View
    {
        $incidents = SecurityIncident::query()
            ->with([
                'securityAlert',
                'assignedTo',
                'createdBy',
            ])
            ->latest('opened_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'security-incidents.index',
            compact('incidents')
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
