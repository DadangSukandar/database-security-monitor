<?php

namespace App\Http\Controllers;

use App\Models\SensitiveDataFinding;
use App\Services\SensitiveDataDiscoveryService;
use Illuminate\Http\Request;
use Throwable;

class SensitiveDataDiscoveryController extends Controller
{
    public function index(
        Request $request
    ) {
        $teamId =
            (int) $request->user()->current_team_id;

        $teamFindingQuery =
            SensitiveDataFinding::query()
                ->whereHas(
                    'column.table.database.databaseConnection',
                    function ($query) use ($teamId): void {
                        $query->forTeam(
                            $teamId
                        );
                    }
                );

        $findings =
            (clone $teamFindingQuery)
                ->with([
                    'column.table.database.databaseConnection',
                ])
                ->latest()
                ->paginate(25);

        $total =
            (clone $teamFindingQuery)
                ->count();

        $critical =
            (clone $teamFindingQuery)
                ->where(
                    'risk_level',
                    'CRITICAL'
                )
                ->count();

        $high =
            (clone $teamFindingQuery)
                ->where(
                    'risk_level',
                    'HIGH'
                )
                ->count();

        $medium =
            (clone $teamFindingQuery)
                ->where(
                    'risk_level',
                    'MEDIUM'
                )
                ->count();

        $low =
            (clone $teamFindingQuery)
                ->where(
                    'risk_level',
                    'LOW'
                )
                ->count();

        return view(
            'sensitive-data.index',
            compact(
                'findings',
                'total',
                'critical',
                'high',
                'medium',
                'low'
            )
        );
    }

    public function scan(
        Request $request,
        SensitiveDataDiscoveryService $service
    ) {
        $teamId =
            (int) $request->user()->current_team_id;

        try {

            $result =
                $service->scan(
                    $teamId
                );

            return redirect()
                ->route(
                    'sensitive-data.index'
                )
                ->with(
                    'success',
                    'Sensitive Data Discovery selesai. '.
                    $result['findings'].
                    ' finding ditemukan dari '.
                    $result['columns'].
                    ' column.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->route(
                    'sensitive-data.index'
                )
                ->with(
                    'error',
                    'Scan gagal: '.
                    $this->safeExceptionDetail($e)
                );
        }
    }
}
