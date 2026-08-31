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
        /*
        |--------------------------------------------------------------------------
        | Database Connections
        |--------------------------------------------------------------------------
        */

        $totalConnections = DatabaseConnection::count();

        $activeConnections = DatabaseConnection::query()
            ->where('is_active', true)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Security Score
        |--------------------------------------------------------------------------
        */

        $securityScore = $securityScoreService->calculate();


        /*
        |--------------------------------------------------------------------------
        | Security Findings
        |--------------------------------------------------------------------------
        */

        $recentSecurityFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->latest('detected_at')
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Security Finding Statistics
        |--------------------------------------------------------------------------
        */

        $criticalFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->where('severity', 'CRITICAL')
            ->count();

        $highFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->where('severity', 'HIGH')
            ->count();

        $mediumFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->where('severity', 'MEDIUM')
            ->count();

        $lowFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->where('severity', 'LOW')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Total Findings
        |--------------------------------------------------------------------------
        */

        $totalFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recent Findings
        |--------------------------------------------------------------------------
        */

        $recentFindings = SecurityFinding::query()
            ->where('status', 'OPEN')
            ->latest('detected_at')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Database Connections List
        |--------------------------------------------------------------------------
        */

        $databaseConnections = DatabaseConnection::query()
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

            'recentSecurityFindings' =>
                $recentSecurityFindings,

            'recentFindings' =>
                $recentFindings,

            'criticalFindings' =>
                $criticalFindings,

            'highFindings' =>
                $highFindings,

            'mediumFindings' =>
                $mediumFindings,

            'lowFindings' =>
                $lowFindings,

            'totalFindings' =>
                $totalFindings,

            'databaseConnections' =>
                $databaseConnections,
        ]);
    }
}