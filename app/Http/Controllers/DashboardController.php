<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
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
            SecurityFinding::query()
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

        $criticalFindings =
            (int) $securityScore['critical'];

        $highFindings =
            (int) $securityScore['high'];

        $mediumFindings =
            (int) $securityScore['medium'];

        $lowFindings =
            (int) $securityScore['low'];

        $totalFindings =
            (int) $securityScore['total'];

        /*
        |--------------------------------------------------------------------------
        | Recent Findings
        |--------------------------------------------------------------------------
        */

        $recentFindings =
            $recentSecurityFindings
                ->take(5)
                ->values();

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
