<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\SecurityScoreService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(

        Request $request,
        SecurityScoreService $securityScoreService
    ) {

        $teamId =
            (int) $request->user()->current_team_id;

        $teamConnectionQuery =
            DatabaseConnection::query()
                ->forTeam($teamId);

        $teamFindingQuery =
            (clone $teamFindingQuery)
                ->forTeam($teamId);
        /*
        |--------------------------------------------------------------------------
        | Database Connections
        |--------------------------------------------------------------------------
        */

        $totalConnections =
            (clone $teamConnectionQuery)
                ->count();

        $activeConnections =
            (clone $teamConnectionQuery)
                ->where(
                    'is_active',
                    true
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        $securityScore =
            $securityScoreService->calculate(
                $teamId
            );

        /*
        |--------------------------------------------------------------------------
        | Security Findings
        |--------------------------------------------------------------------------
        */

        $recentSecurityFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->latest('detected_at')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Security Finding Statistics
        |--------------------------------------------------------------------------
        */

        $criticalFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->where('severity', 'CRITICAL')
            ->count();

        $highFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->where('severity', 'HIGH')
            ->count();

        $mediumFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->where('severity', 'MEDIUM')
            ->count();

        $lowFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->where('severity', 'LOW')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Total Findings
        |--------------------------------------------------------------------------
        */

        $totalFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Findings
        |--------------------------------------------------------------------------
        */

        $recentFindings = (clone $teamFindingQuery)
            ->where('status', 'OPEN')
            ->latest('detected_at')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Database Connections List
        |--------------------------------------------------------------------------
        */

        $databaseConnections =
            (clone $teamConnectionQuery)
                ->latest()
                ->limit(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Return Dashboard
        |--------------------------------------------------------------------------
        */

        return view('dashboard', [
            'totalConnections' => $totalConnections,

            'activeConnections' => $activeConnections,

            'securityScore' => $securityScore,

            'recentSecurityFindings' => $recentSecurityFindings,

            'recentFindings' => $recentFindings,

            'criticalFindings' => $criticalFindings,

            'highFindings' => $highFindings,

            'mediumFindings' => $mediumFindings,

            'lowFindings' => $lowFindings,

            'totalFindings' => $totalFindings,

            'databaseConnections' => $databaseConnections,
        ]);
    }
}
