<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Services\SecurityAuditScanner;
use App\Services\SecurityFindingLifecycleService;
use Illuminate\Http\Request;
use Throwable;

class SecurityAuditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'in:CRITICAL,HIGH,MEDIUM,LOW'],
            'status' => ['nullable', 'string', 'in:OPEN,RESOLVED,IGNORED'],
            'database_connection_id' => ['nullable', 'integer'],
        ]);

        $teamId = (int) $request->user()->current_team_id;

        $teamFindingQuery = SecurityFinding::query()
            ->forTeam($teamId);

        $query = (clone $teamFindingQuery)
            ->latest('detected_at');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('database_name', 'like', "%{$search}%")
                    ->orWhere('object_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Severity
        |--------------------------------------------------------------------------
        */

        if ($request->filled('severity')) {
            $query->where(
                'severity',
                $request->input('severity')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Database connection
        |--------------------------------------------------------------------------
        */

        if ($request->filled('database_connection_id')) {
            $query->where(
                'database_connection_id',
                $request->input('database_connection_id')
            );
        }

        $findings = $query
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $auditStats =
            (clone $teamFindingQuery)
                ->selectRaw(
                    "
                    COUNT(*) AS total_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS open_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'RESOLVED'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS resolved_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'CRITICAL'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS critical_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'HIGH'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS high_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'MEDIUM'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS medium_findings,

                    COALESCE(
                        SUM(
                            CASE
                                WHEN status = 'OPEN'
                                    AND severity = 'LOW'
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS low_findings
                    "
                )
                ->first();

        $total =
            (int) ($auditStats?->total_findings ?? 0);

        $open =
            (int) ($auditStats?->open_findings ?? 0);

        $resolved =
            (int) ($auditStats?->resolved_findings ?? 0);

        $critical =
            (int) ($auditStats?->critical_findings ?? 0);

        $high =
            (int) ($auditStats?->high_findings ?? 0);

        $medium =
            (int) ($auditStats?->medium_findings ?? 0);

        $low =
            (int) ($auditStats?->low_findings ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        $score = $this->calculateSecurityScore(
            $critical,
            $high,
            $medium,
            $low
        );

        $connections =
            DatabaseConnection::query()
                ->forTeam($teamId)
                ->orderBy('name')
                ->get();

        return view(
            'security-audit.index',
            compact(
                'findings',
                'total',
                'critical',
                'high',
                'medium',
                'low',
                'open',
                'resolved',
                'score',
                'connections'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    public function scan(
        Request $request,
        SecurityAuditScanner $scanner
    ) {

        $validated = $request->validate([
            'database_connection_id' => [
                'required',
                'integer',
            ],
        ]);

        $teamId = (int) $request->user()->current_team_id;

        $connection =
            DatabaseConnection::query()
                ->forTeam($teamId)
                ->findOrFail(
                    (int) $validated['database_connection_id']
                );

        try {

            $result = $scanner->scan(
                $connection
            );

            return redirect()
                ->route('security-audit.index')
                ->with(
                    'success',
                    'Security audit berhasil. '.
                    $result['total'].
                    ' finding ditemukan.'
                );

        } catch (Throwable $e) {

            return back()->withErrors([
                'scan' => 'Security audit gagal: '.
                    $this->safeExceptionDetail($e),
            ]);
        }
    }

    public function show(
        Request $request,
        SecurityFinding $securityFinding
    ) {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $securityFinding
        );

        return view(
            'security-audit.show',
            compact(
                'securityFinding'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve
    |--------------------------------------------------------------------------
    */

    public function resolve(
        Request $request,
        SecurityFinding $securityFinding,
        SecurityFindingLifecycleService $lifecycle,
    ) {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $securityFinding
        );
        try {
            $lifecycle->resolve($securityFinding, (int) auth()->id());

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
        SecurityFinding $securityFinding,
        SecurityFindingLifecycleService $lifecycle,
    ) {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $securityFinding
        );
        try {
            $lifecycle->ignore($securityFinding, (int) auth()->id());

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
        SecurityFinding $securityFinding,
        SecurityFindingLifecycleService $lifecycle,
    ) {
        $this->ensureFindingBelongsToCurrentTeam(
            $request,
            $securityFinding
        );
        try {
            $lifecycle->reopen($securityFinding, (int) auth()->id());

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

    /*
    |--------------------------------------------------------------------------
    | Security Score
    |--------------------------------------------------------------------------
    */

    private function calculateSecurityScore(
        int $critical,
        int $high,
        int $medium,
        int $low
    ): int {
        $deduction =
            ($critical * 30) +
            ($high * 15) +
            ($medium * 7) +
            ($low * 2);

        return max(
            0,
            100 - $deduction
        );
    }

    private function ensureFindingBelongsToCurrentTeam(
        Request $request,
        SecurityFinding $securityFinding
    ): void {
        $teamId = $request->user()?->current_team_id;

        abort_if(
            $teamId === null ||
            (int) $securityFinding->team_id !== (int) $teamId,
            404
        );
    }
}
