<?php

namespace App\Http\Controllers;

use App\Models\SecurityAlert;
use App\Services\SecurityAlertAssignmentService;
use App\Services\SecurityAlertInvestigationService;
use App\Services\SecurityAlertLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SecurityAlertController extends Controller
{
    /**
     * =========================================================
     * INDEX
     * =========================================================
     */
    public function index(Request $request): View
    {
        $query = SecurityAlert::query()
            ->canonical()
            ->with([
                'databaseConnection',
                'databaseActivity',
            ]);

        /*
         * SEARCH
         */
        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'title',
                    'like',
                    '%'.$search.'%'
                )
                    ->orWhere(
                        'description',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'database_name',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'username',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'client_ip',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'alert_type',
                        'like',
                        '%'.$search.'%'
                    );
            });
        }

        /*
         * SEVERITY
         */
        if ($request->filled('severity')) {

            $severity = strtoupper(
                $request->input('severity')
            );

            if (in_array(
                $severity,
                [
                    'CRITICAL',
                    'HIGH',
                    'MEDIUM',
                    'LOW',
                ],
                true
            )) {

                $query->where(
                    'severity',
                    $severity
                );
            }
        }

        /*
         * STATUS
         */
        if ($request->filled('status')) {

            $status = strtoupper(
                $request->input('status')
            );

            if (in_array(
                $status,
                [
                    'OPEN',
                    'ACKNOWLEDGED',
                    'INVESTIGATING',
                    'RESOLVED',
                ],
                true
            )) {

                $query->where(
                    'status',
                    $status
                );
            }
        }

        /*
         * ALERT TYPE
         */
        if ($request->filled('alert_type')) {

            $query->where(
                'alert_type',
                $request->input('alert_type')
            );
        }

        /*
         * DATABASE
         */
        if ($request->filled('database')) {

            $query->where(
                'database_name',
                $request->input('database')
            );
        }

        /*
         * SORT
         */
        $query->orderByRaw("
            CASE UPPER(severity)
                WHEN 'CRITICAL' THEN 1
                WHEN 'HIGH' THEN 2
                WHEN 'MEDIUM' THEN 3
                WHEN 'LOW' THEN 4
                ELSE 5
            END
        ");

        $query->latest('detected_at');

        /*
         * PAGINATION
         */
        $alerts = $query
            ->paginate(15)
            ->withQueryString();

        /*
         * STATISTICS
         */
        $totalAlerts =
            SecurityAlert::query()->canonical()->count();

        $openAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'OPEN'
            )->count();

        $acknowledgedAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'ACKNOWLEDGED'
            )->count();

        $investigatingAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'INVESTIGATING'
            )->count();

        $resolvedAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'RESOLVED'
            )->count();

        $criticalAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'OPEN'
            )
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->count();

        $highAlerts =
            SecurityAlert::query()->canonical()->where(
                'status',
                'OPEN'
            )
                ->where(
                    'severity',
                    'HIGH'
                )
                ->count();

        /*
         * FILTER OPTIONS
         */
        $databases =
            SecurityAlert::query()
                ->canonical()
                ->whereNotNull('database_name')
                ->where(
                    'database_name',
                    '!=',
                    ''
                )
                ->distinct()
                ->orderBy('database_name')
                ->pluck('database_name');

        $alertTypes =
            SecurityAlert::query()
                ->canonical()
                ->whereNotNull('alert_type')
                ->where(
                    'alert_type',
                    '!=',
                    ''
                )
                ->distinct()
                ->orderBy('alert_type')
                ->pluck('alert_type');

        return view(
            'security-alerts.index',
            compact(
                'alerts',

                'totalAlerts',
                'openAlerts',
                'acknowledgedAlerts',
                'investigatingAlerts',
                'resolvedAlerts',

                'criticalAlerts',
                'highAlerts',

                'databases',
                'alertTypes'
            )
        );
    }

    /**
     * =========================================================
     * SHOW
     * =========================================================
     */
    public function show(
        Request $request,
        SecurityAlert $alert
    ): View {
        $alert->load([
            'databaseConnection',
            'databaseActivity',
            'histories.user',
            'assignedTo',
        ]);

        $teamMembers = collect();

        if ($alert->canonical_alert_id === null) {
            $currentTeam = $request->user()?->currentTeam;

            if ($currentTeam !== null) {
                $teamMembers = $currentTeam
                    ->members()
                    ->orderBy('users.name')
                    ->get();
            }
        }

        return view('security-alerts.show', [
            'alert' => $alert,
            'teamMembers' => $teamMembers,
        ]);
    }

    /**
     * =========================================================
     * ACKNOWLEDGE
     * =========================================================
     */
    public function acknowledge(
        SecurityAlert $alert,
        SecurityAlertLifecycleService $lifecycle
    ): RedirectResponse {
        try {
            $lifecycle->acknowledge($alert, auth()->id());
        } catch (Throwable $exception) {
            return back()->withErrors(['alert' => $exception->getMessage()]);
        }

        return back()->with(
            'success',
            'Security alert berhasil di-acknowledge.'
        );
    }

    /**
     * =========================================================
     * START INVESTIGATION
     * =========================================================
     */
    public function investigate(
        Request $request,
        SecurityAlert $alert,
        SecurityAlertLifecycleService $lifecycle
    ): RedirectResponse {
        $validated = $request->validate([
            'investigation_note' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $lifecycle->investigate(
                $alert,
                $validated['investigation_note'] ?? null,
                auth()->id()
            );

            return back()->with(
                'success',
                'Investigasi security alert berhasil dimulai.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'alert' => 'Gagal memulai investigasi: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * ADD INVESTIGATION NOTE
     * =========================================================
     */
    public function addInvestigationNote(
        Request $request,
        SecurityAlert $alert,
        SecurityAlertInvestigationService $investigation
    ): RedirectResponse {
        $validated = $request->validate([
            'investigation_note' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $investigation->addNote(
                $alert,
                $validated['investigation_note'],
                $request->user()->id
            );

            return back()->with(
                'success',
                'Catatan investigasi berhasil ditambahkan.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'alert' => 'Gagal menambahkan catatan investigasi: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * ASSIGN / REASSIGN
     * =========================================================
     */
    public function assign(
        Request $request,
        SecurityAlert $alert,
        SecurityAlertAssignmentService $assignment
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
                $alert,
                $assignee,
                $actor->id
            );

            return back()->with(
                'success',
                'PIC security alert berhasil diperbarui.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'alert' => 'Gagal assign security alert: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * UNASSIGN
     * =========================================================
     */
    public function unassign(
        Request $request,
        SecurityAlert $alert,
        SecurityAlertAssignmentService $assignment
    ): RedirectResponse {
        try {
            $assignment->unassign(
                $alert,
                $request->user()->id
            );

            return back()->with(
                'success',
                'PIC security alert berhasil dilepas.'
            );
        } catch (Throwable $e) {
            return back()->withErrors([
                'alert' => 'Gagal unassign security alert: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * RESOLVE
     * =========================================================
     */
    public function resolve(
        Request $request,
        SecurityAlert $alert,
        SecurityAlertLifecycleService $lifecycle
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
                $alert,
                $validated['resolution_note'],
                auth()->id()
            );

            return back()->with(
                'success',
                'Security alert berhasil diselesaikan.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'alert' => 'Gagal resolve alert: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * REOPEN
     * =========================================================
     */
    public function reopen(
        SecurityAlert $alert,
        SecurityAlertLifecycleService $lifecycle
    ): RedirectResponse {
        try {

            $lifecycle->reopen($alert, auth()->id());

            return back()->with(
                'success',
                'Security alert berhasil dibuka kembali.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'alert' => 'Gagal reopen alert: '.
                    $e->getMessage(),
            ]);
        }
    }
}
