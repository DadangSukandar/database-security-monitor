<?php

namespace App\Http\Controllers;

use App\Models\SecurityAlert;
use App\Services\SecurityAlertLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function show(SecurityAlert $alert): View
    {
        $alert->load([
            'databaseConnection',
            'databaseActivity',
            'histories',
        ]);

        return view(
            'security-alerts.show',
            compact('alert')
        );
    }

    /**
     * =========================================================
     * ACKNOWLEDGE
     * =========================================================
     */
    public function acknowledge(
        SecurityAlert $alert,
        SecurityAlertLifecycleService $lifecycle
    ): RedirectResponse
    {
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
    ): RedirectResponse
    {
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
