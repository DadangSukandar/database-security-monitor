<?php

namespace App\Http\Controllers;

use App\Models\SecurityFinding;
use App\Models\SecurityFindingHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        /*
         * =====================================================
         * BASE QUERY
         * =====================================================
         */

        $query = SecurityFinding::query();

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
            SecurityFinding::count();

        /*
         * =====================================================
         * STATUS COUNTS
         * =====================================================
         */

        $openFindings =
            SecurityFinding::where(
                'status',
                'OPEN'
            )->count();

        $resolvedFindings =
            SecurityFinding::where(
                'status',
                'RESOLVED'
            )->count();

        $ignoredFindings =
            SecurityFinding::where(
                'status',
                'IGNORED'
            )->count();

        /*
         * =====================================================
         * SEVERITY COUNTS
         * =====================================================
         *
         * Hanya finding OPEN yang dihitung sebagai
         * active security risk.
         */

        $critical =
            SecurityFinding::where(
                'status',
                'OPEN'
            )
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->count();

        $high =
            SecurityFinding::where(
                'status',
                'OPEN'
            )
                ->where(
                    'severity',
                    'HIGH'
                )
                ->count();

        $medium =
            SecurityFinding::where(
                'status',
                'OPEN'
            )
                ->where(
                    'severity',
                    'MEDIUM'
                )
                ->count();

        $low =
            SecurityFinding::where(
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
            SecurityFinding::query()
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
            SecurityFinding::query()
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
        SecurityFinding $finding
    ): View {
        $finding->load([
            'databaseConnection',
            'histories',
        ]);

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
        SecurityFinding $finding
    ): RedirectResponse {

        try {

            DB::transaction(function () use (
                $finding
            ) {

                /*
                 * Simpan status lama.
                 */

                $oldStatus =
                    strtoupper(
                        (string) $finding->status
                    );

                /*
                 * Jika sudah RESOLVED,
                 * tidak perlu membuat history baru.
                 */

                if (
                    $oldStatus === 'RESOLVED'
                ) {

                    return;
                }

                /*
                 * Update finding.
                 */

                $finding->update([
                    'status' => 'RESOLVED',
                ]);

                /*
                 * Simpan history.
                 */

                SecurityFindingHistory::create([

                    'security_finding_id' => $finding->id,

                    'action' => 'RESOLVE',

                    'old_status' => $oldStatus,

                    'new_status' => 'RESOLVED',

                    'notes' => 'Security finding ditandai sebagai resolved.',

                    'user_id' => auth()->id(),
                ]);
            });

            return back()->with(
                'success',
                'Security finding berhasil ditandai sebagai resolved.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'finding' => 'Gagal resolve finding: '.
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * =========================================================
     * IGNORE
     * =========================================================
     */
    public function ignore(
        SecurityFinding $finding
    ): RedirectResponse {

        try {

            DB::transaction(function () use (
                $finding
            ) {

                /*
                 * Status lama.
                 */

                $oldStatus =
                    strtoupper(
                        (string) $finding->status
                    );

                /*
                 * Jika sudah IGNORED,
                 * tidak perlu history duplikat.
                 */

                if (
                    $oldStatus === 'IGNORED'
                ) {

                    return;
                }

                /*
                 * Update status.
                 */

                $finding->update([
                    'status' => 'IGNORED',
                ]);

                /*
                 * Simpan history.
                 */

                SecurityFindingHistory::create([

                    'security_finding_id' => $finding->id,

                    'action' => 'IGNORE',

                    'old_status' => $oldStatus,

                    'new_status' => 'IGNORED',

                    'notes' => 'Security finding diabaikan.',

                    'user_id' => auth()->id(),
                ]);
            });

            return back()->with(
                'success',
                'Security finding berhasil diabaikan.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'finding' => 'Gagal ignore finding: '.
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
        SecurityFinding $finding
    ): RedirectResponse {

        try {

            DB::transaction(function () use (
                $finding
            ) {

                /*
                 * Status lama.
                 */

                $oldStatus =
                    strtoupper(
                        (string) $finding->status
                    );

                /*
                 * Jika sudah OPEN,
                 * tidak perlu membuat history baru.
                 */

                if (
                    $oldStatus === 'OPEN'
                ) {

                    return;
                }

                /*
                 * Update status.
                 */

                $finding->update([
                    'status' => 'OPEN',
                ]);

                /*
                 * Simpan history.
                 */

                SecurityFindingHistory::create([

                    'security_finding_id' => $finding->id,

                    'action' => 'REOPEN',

                    'old_status' => $oldStatus,

                    'new_status' => 'OPEN',

                    'notes' => 'Security finding dibuka kembali.',

                    'user_id' => auth()->id(),
                ]);
            });

            return back()->with(
                'success',
                'Security finding berhasil dibuka kembali.'
            );

        } catch (Throwable $e) {

            return back()->withErrors([
                'finding' => 'Gagal reopen finding: '.
                    $e->getMessage(),
            ]);
        }
    }
}
