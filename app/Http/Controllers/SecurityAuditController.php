<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Services\SecurityAuditScanner;

class SecurityAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityFinding::query()
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

        $total = SecurityFinding::count();

        $critical = SecurityFinding::where(
            'severity',
            'CRITICAL'
        )->where(
            'status',
            'OPEN'
        )->count();

        $high = SecurityFinding::where(
            'severity',
            'HIGH'
        )->where(
            'status',
            'OPEN'
        )->count();

        $medium = SecurityFinding::where(
            'severity',
            'MEDIUM'
        )->where(
            'status',
            'OPEN'
        )->count();

        $low = SecurityFinding::where(
            'severity',
            'LOW'
        )->where(
            'status',
            'OPEN'
        )->count();

        $open = SecurityFinding::where(
            'status',
            'OPEN'
        )->count();

        $resolved = SecurityFinding::where(
            'status',
            'RESOLVED'
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        $score = $this->calculateSecurityScore();

        $connections = DatabaseConnection::orderBy(
            'name'
        )->get();

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
        $connectionId = $request->input(
            'database_connection_id'
        );

        if (!$connectionId) {
            return back()->withErrors([
                'scan' =>
                    'Pilih database connection terlebih dahulu.'
            ]);
        }

        $connection = DatabaseConnection::find(
            $connectionId
        );

        if (!$connection) {
            return back()->withErrors([
                'scan' =>
                    'Database connection tidak ditemukan.'
            ]);
        }

        try {

            $result = $scanner->scan(
                $connection
            );

            return redirect()
                ->route('security-audit.index')
                ->with(
                    'success',
                    'Security audit berhasil. ' .
                    $result['total'] .
                    ' finding ditemukan.'
                );

        } catch (Throwable $e) {

            return back()->withErrors([
                'scan' =>
                    'Security audit gagal: ' .
                    $e->getMessage()
            ]);
        }
    }

    public function show(SecurityFinding $securityFinding)
    {
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

    public function resolve(SecurityFinding $securityFinding)
    {
        $securityFinding->update([
            'status' => 'RESOLVED',
            'resolved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Security finding berhasil ditandai sebagai resolved.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ignore
    |--------------------------------------------------------------------------
    */

    public function ignore(SecurityFinding $securityFinding)
    {
        $securityFinding->update([
            'status' => 'IGNORED',
        ]);

        return back()->with(
            'success',
            'Security finding berhasil diabaikan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Re-open
    |--------------------------------------------------------------------------
    */

    public function reopen(SecurityFinding $securityFinding)
    {
        $securityFinding->update([
            'status' => 'OPEN',
            'resolved_at' => null,
        ]);

        return back()->with(
            'success',
            'Security finding berhasil dibuka kembali.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Security Score
    |--------------------------------------------------------------------------
    */

    private function calculateSecurityScore(): int
    {
        $critical = SecurityFinding::where(
            'severity',
            'CRITICAL'
        )->where(
            'status',
            'OPEN'
        )->count();

        $high = SecurityFinding::where(
            'severity',
            'HIGH'
        )->where(
            'status',
            'OPEN'
        )->count();

        $medium = SecurityFinding::where(
            'severity',
            'MEDIUM'
        )->where(
            'status',
            'OPEN'
        )->count();

        $low = SecurityFinding::where(
            'severity',
            'LOW'
        )->where(
            'status',
            'OPEN'
        )->count();

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
}