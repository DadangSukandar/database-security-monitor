<?php

namespace App\Http\Controllers;

use App\Services\SecurityIncidentReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SecurityIncidentReportController extends Controller
{
    public function index(
        Request $request,
        SecurityIncidentReportService $reportService
    ): View {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $startDate = Carbon::createFromFormat(
            'Y-m-d',
            $filters['start_date'] ?? now()->subDays(29)->toDateString()
        )->startOfDay();
        $endDate = Carbon::createFromFormat(
            'Y-m-d',
            $filters['end_date'] ?? now()->toDateString()
        )->endOfDay();

        if ($startDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'start_date' => 'Start date must be on or before end date.',
            ]);
        }

        if ($startDate->diffInDays($endDate) > 366) {
            throw ValidationException::withMessages([
                'start_date' => 'Report range may not exceed 366 days.',
            ]);
        }

        $report = $reportService->build($startDate, $endDate);
        $auditActivities = $reportService
            ->auditQuery($startDate, $endDate)
            ->paginate(25, ['*'], 'audit_page')
            ->withQueryString();

        return view('security-incidents.report', compact(
            'report',
            'auditActivities',
            'startDate',
            'endDate'
        ));
    }
}
