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
            ->with('databaseConnection')
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

        $total =
            (clone $teamFindingQuery)
                ->count();

        $critical =
            (clone $teamFindingQuery)
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $high =
            (clone $teamFindingQuery)
                ->where(
                    'severity',
                    'HIGH'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $medium =
            (clone $teamFindingQuery)
                ->where(
                    'severity',
                    'MEDIUM'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $low =
            (clone $teamFindingQuery)
                ->where(
                    'severity',
                    'LOW'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $open =
            (clone $teamFindingQuery)
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $resolved =
            (clone $teamFindingQuery)
                ->where(
                    'status',
                    'RESOLVED'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        $score = $this->calculateSecurityScore(
            $teamId
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

        $securityFinding->load(
            'databaseConnection'
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
        int $teamId
    ): int {
        $findingQuery = SecurityFinding::query()
            ->forTeam($teamId);

        $critical =
            (clone $findingQuery)
                ->where(
                    'severity',
                    'CRITICAL'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $high =
            (clone $findingQuery)
                ->where(
                    'severity',
                    'HIGH'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $medium =
            (clone $findingQuery)
                ->where(
                    'severity',
                    'MEDIUM'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

        $low =
            (clone $findingQuery)
                ->where(
                    'severity',
                    'LOW'
                )
                ->where(
                    'status',
                    'OPEN'
                )
                ->count();

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
