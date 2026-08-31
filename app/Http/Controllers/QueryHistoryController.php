<?php

namespace App\Http\Controllers;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use Illuminate\Http\Request;

class QueryHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = DatabaseActivity::query()
            ->with('databaseConnection')
            ->latest('executed_at')
            ->latest('id');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                $q->where('query', 'like', "%{$search}%")
                    ->orWhere('table_name', 'like', "%{$search}%")
                    ->orWhere('database_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Connection Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('connection_id')) {

            $query->where(
                'database_connection_id',
                $request->input('connection_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Action Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('action')) {

            $query->where(
                'action',
                strtoupper($request->input('action'))
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                strtolower($request->input('status'))
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {

            $query->whereDate(
                'executed_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {

            $query->whereDate(
                'executed_at',
                '<=',
                $request->input('date_to')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $baseQuery = clone $query;

        $total = (clone $baseQuery)->count();

        $success = (clone $baseQuery)
            ->where('status', 'success')
            ->count();

        $failed = (clone $baseQuery)
            ->where('status', 'failed')
            ->count();

        $select = (clone $baseQuery)
            ->where('action', 'SELECT')
            ->count();

        $averageExecution = (clone $baseQuery)
            ->whereNotNull('execution_time_ms')
            ->avg('execution_time_ms');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $activities = $query
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Connections
        |--------------------------------------------------------------------------
        */

        $connections = DatabaseConnection::query()
            ->orderBy('name')
            ->get();

        return view(
            'query-history.index',
            compact(
                'activities',
                'connections',
                'total',
                'success',
                'failed',
                'select',
                'averageExecution'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    public function show(DatabaseActivity $databaseActivity)
    {
        $databaseActivity->load(
            'databaseConnection'
        );

        return view(
            'query-history.show',
            compact('databaseActivity')
        );
    }
}