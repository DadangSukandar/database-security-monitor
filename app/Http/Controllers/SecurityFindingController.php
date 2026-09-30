<?php

namespace App\Http\Controllers;

use App\Models\SecurityFinding;
use App\Services\SecurityFindingLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SecurityFindingController extends Controller
{
    /**
     * =========================================================
     * SECURITY FINDINGS INDEX
     * =========================================================
     */
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:CRITICAL,HIGH,MEDIUM,LOW'],
            'status' => ['nullable', 'string', 'in:OPEN,RESOLVED,IGNORED'],
            'database' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $teamId = (int) $request->user()->current_team_id;

        $teamFindingQuery = SecurityFinding::query()
            ->forTeam($teamId);
        /*
         * =====================================================
         * BASE QUERY
         * =====================================================
         */

        $query = clone $teamFindingQuery;

        /*
         * =====================================================
         * SEARCH
         * =====================================================
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
                        'finding_type',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'category',
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
                        'object_name',
                        'like',
                        '%'.$search.'%'
                    );
            });
        }

        /*
         * =====================================================
         * SEVERITY FILTER
         * =====================================================
         */

        if ($request->filled('severity')) {

            $severity = strtoupper(
                $request->input('severity')
            );

            if (
                in_array(
                    $severity,
                    [
                        'CRITICAL',
                        'HIGH',
                        'MEDIUM',
                        'LOW',
                    ],
                    true
                )
            ) {

                $query->where(
                    'severity',
                    $severity
                );
            }
        }

        /*
         * =====================================================
         * STATUS FILTER
         * =====================================================
         */

        if ($request->filled('status')) {

            $status = strtoupper(
                $request->input('status')
            );

            if (
                in_array(
                    $status,
                    [
                        'OPEN',
                        'RESOLVED',
                        'IGNORED',
                    ],
                    true
                )
            ) {

                $query->where(
                    'status',
                    $status
                );
            }
        }

        /*
         * =====================================================
         * DATABASE FILTER
         * =====================================================
         */

        if ($request->filled('database')) {

            $query->where(
                'database_name',
                $request->input('database')
            );
        }

        /*
         * =====================================================
         * CATEGORY FILTER
         * =====================================================
         */

        if ($request->filled('category')) {

            $query->where(
                'category',
                $request->input('category')
            );
        }

        /*
         * =====================================================
         * SORTING
         * =====================================================
         */

        $query->orderByRaw(
            "
            CASE UPPER(severity)
                WHEN 'CRITICAL' THEN 1
                WHEN 'HIGH' THEN 2
                WHEN 'MEDIUM' THEN 3
                WHEN 'LOW' THEN 4
                ELSE 5
            END
            "
        );

        $query->latest('created_at');

        /*
         * =====================================================
         * PAGINATION
         * =====================================================
         */

        $findings = $query
            ->paginate(15)
            ->withQueryString();

        /*
         * =====================================================
         * TOTAL
         * =====================================================
         */

        $totalFindings =
            (clone $teamFindingQuery)
                ->count();

        /*
         * =====================================================
         * STATUS COUNTS
         * =====================================================
         */

        $openFindings =
            (clone $teamFindingQuery)
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $resolvedFindings =
            (clone $teamFindingQuery)
                ->where(
                    'status',
                    'RESOLVED'
                )
                ->count();

        $ignoredFindings =
            (clone $teamFindingQuery)
                ->where(
                    'status',
                    'IGNORED'
                )
                ->count();

        /*
         * =====================================================
         * SEVERITY COUNTS
         * =====================================================
         *
         * Hanya finding OPEN yang dihitung sebagai
         * active security risk.
         */

        $critical =
            (clone $teamFindingQuery)
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
            (clone $teamFindingQuery)
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
            (clone $teamFindingQuery)
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
            (clone $teamFindingQuery)
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
         * =====================================================
         * DATABASE OPTIONS
         * =====================================================
         */

        $databases =
            (clone $teamFindingQuery)
                ->whereNotNull('database_name')
                ->where(
                    'database_name',
                    '!=',
                    ''
                )
                ->distinct()
                ->orderBy('database_name')
                ->pluck('database_name');

        /*
         * =====================================================
         * CATEGORY OPTIONS
         * =====================================================
         */

        $categories =
            (clone $teamFindingQuery)
                ->whereNotNull('category')
                ->where(
                    'category',
                    '!=',
                    ''
                )
                ->distinct()
                ->orderBy('category')
                ->pluck('category');

        /*
         * =====================================================
         * FILTER VALUES
         * =====================================================
         */

        $search =
            $request->input(
                'search',
                ''
            );

        $severity =
            $request->input(
                'severity',
                ''
            );

        $status =
            $request->input(
                'status',
                ''
            );

        $database =
            $request->input(
                'database',
                ''
            );

        $category =
            $request->input(
                'category',
                ''
            );

        /*
         * =====================================================
         * RETURN VIEW
         * =====================================================
         */

        return view(
            'security-findings.index',
            compact(

                'findings',

                'totalFindings',

                'openFindings',

                'resolvedFindings',

                'ignoredFindings',

                'critical',

                'high',

                'medium',

                'low',

                'databases',

                'categories',

                'search',

                'severity',

                'status',

                'database',

                'category'
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
        SecurityFinding $finding
    ): View {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $finding
        );

        return view(
            'security-findings.show',
            compact(
                'finding'
            )
        );
    }

    /**
     * =========================================================
     * RESOLVE
     * =========================================================
     */
    public function resolve(
        Request $request,
        SecurityFinding $finding,
        SecurityFindingLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $finding
        );

        try {
            $lifecycle->resolve($finding, (int) auth()->id());

            return back()->with(
                'success',
                'Security finding berhasil ditandai sebagai resolved.'
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'finding' => 'Gagal resolve finding: '.$this->safeExceptionDetail($exception),
            ]);
        }
    }

    public function ignore(
        Request $request,
        SecurityFinding $finding,
        SecurityFindingLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $finding
        );

        try {
            $lifecycle->ignore($finding, (int) auth()->id());

            return back()->with(
                'success',
                'Security finding berhasil diabaikan.'
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'finding' => 'Gagal ignore finding: '.$this->safeExceptionDetail($exception),
            ]);
        }
    }

    public function reopen(
        Request $request,
        SecurityFinding $finding,
        SecurityFindingLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $finding
        );

        try {
            $lifecycle->reopen($finding, (int) auth()->id());

            return back()->with(
                'success',
                'Security finding berhasil dibuka kembali.'
            );
        } catch (Throwable $exception) {
            return back()->withErrors([
                'finding' => 'Gagal reopen finding: '.$this->safeExceptionDetail($exception),
            ]);
        }
    }

    private function ensureFindingBelongsToCurrentTeam(
        Request $request,
        SecurityFinding $finding
    ): void {
        $teamId = $request->user()?->current_team_id;

        abort_if(
            $teamId === null ||
            (int) $finding->team_id !== (int) $teamId,
            404
        );
    }
}
