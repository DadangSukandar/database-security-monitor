<?php

namespace App\Http\Controllers;

use App\Models\SensitiveDataFinding;
use App\Services\SensitiveDataDiscoveryService;
use Illuminate\Http\Request;
use Throwable;

class SensitiveDataDiscoveryController extends Controller
{
    public function index()
    {
        $findings = SensitiveDataFinding::query()
            ->with([
                'column.table.database'
            ])
            ->latest()
            ->paginate(25);

        $total =
            SensitiveDataFinding::count();

        $critical =
            SensitiveDataFinding::where(
                'risk_level',
                'CRITICAL'
            )->count();

        $high =
            SensitiveDataFinding::where(
                'risk_level',
                'HIGH'
            )->count();

        $medium =
            SensitiveDataFinding::where(
                'risk_level',
                'MEDIUM'
            )->count();

        $low =
            SensitiveDataFinding::where(
                'risk_level',
                'LOW'
            )->count();

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
        SensitiveDataDiscoveryService $service
    ) {
        try {

            $result =
                $service->scan();

            return redirect()
                ->route(
                    'sensitive-data.index'
                )
                ->with(
                    'success',
                    'Sensitive Data Discovery selesai. ' .
                    $result['findings'] .
                    ' finding ditemukan dari ' .
                    $result['columns'] .
                    ' column.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->route(
                    'sensitive-data.index'
                )
                ->with(
                    'error',
                    'Scan gagal: ' .
                    $e->getMessage()
                );
        }
    }
}