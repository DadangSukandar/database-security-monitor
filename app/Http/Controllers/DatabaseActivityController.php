<?php

namespace App\Http\Controllers;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use Illuminate\Http\Request;

class DatabaseActivityController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $search = trim(
            $request->input('search', '')
        );

        $action = $request->input(
            'action'
        );

        $status = $request->input(
            'status'
        );

        $connectionId = $request->input(
            'database_connection_id'
        );


        /*
        |--------------------------------------------------------------------------
        | Query Activities
        |--------------------------------------------------------------------------
        */

        $query = DatabaseActivity::query()
            ->with('databaseConnection')
            ->latest('executed_at');


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'query',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'username',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'database_name',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'table_name',
                    'like',
                    '%' . $search . '%'
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Action Filter
        |--------------------------------------------------------------------------
        */

        if ($action) {

            $query->where(
                'action',
                $action
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status) {

            $query->where(
                'status',
                $status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Connection Filter
        |--------------------------------------------------------------------------
        */

        if ($connectionId) {

            $query->where(
                'database_connection_id',
                $connectionId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $activities = $query
            ->paginate(25)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $totalActivities =
            DatabaseActivity::count();


        $successfulActivities =
            DatabaseActivity::where(
                'status',
                'SUCCESS'
            )->count();


        $failedActivities =
            DatabaseActivity::where(
                'status',
                'FAILED'
            )->count();


        $averageExecutionTime =
            DatabaseActivity::query()
                ->whereNotNull(
                    'execution_time_ms'
                )
                ->avg(
                    'execution_time_ms'
                );


        /*
        |--------------------------------------------------------------------------
        | Connections
        |--------------------------------------------------------------------------
        */

        $connections =
            DatabaseConnection::query()
                ->orderBy('name')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return view(
            'database-activities.index',
            compact(
                'activities',
                'connections',
                'search',
                'action',
                'status',
                'connectionId',
                'totalActivities',
                'successfulActivities',
                'failedActivities',
                'averageExecutionTime'
            )
        );
    }


    public function show(
        DatabaseActivity $databaseActivity
    ) {

        $databaseActivity->load(
            'databaseConnection'
        );


        return view(
            'database-activities.show',
            compact(
                'databaseActivity'
            )
        );
    }
}