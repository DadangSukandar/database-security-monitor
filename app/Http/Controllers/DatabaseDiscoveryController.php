<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use App\Models\DiscoveredColumn;
use App\Services\DatabaseDiscoveryService;
use Illuminate\Http\Request;
use Throwable;

class DatabaseDiscoveryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $databases =
            DiscoveredDatabase::query()
                ->withCount('tables')
                ->with('databaseConnection')
                ->latest()
                ->get();


        $connections =
            DatabaseConnection::query()
                ->orderBy('name')
                ->get();


        $totalDatabases =
            $databases->count();


        $totalTables =
            DiscoveredTable::count();


        $totalColumns =
            DiscoveredColumn::count();


        return view(
            'database-discovery.index',
            compact(
                'databases',
                'connections',
                'totalDatabases',
                'totalTables',
                'totalColumns'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SCAN
    |--------------------------------------------------------------------------
    */

    public function scan(
        DatabaseConnection $databaseConnection,
        DatabaseDiscoveryService $service
    ) {

        try {

            $result =
                $service->scan(
                    $databaseConnection
                );


            return redirect()
                ->route(
                    'database-discovery.index'
                )
                ->with(
                    'success',
                    'Discovery selesai. ' .
                    $result['tables'] .
                    ' tables dan ' .
                    $result['columns'] .
                    ' columns ditemukan.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->route(
                    'database-discovery.index'
                )
                ->with(
                    'error',
                    'Discovery gagal: ' .
                    $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW DATABASE
    |--------------------------------------------------------------------------
    */

    public function show(
        DiscoveredDatabase $discoveredDatabase
    ) {

        $discoveredDatabase->load([
            'databaseConnection',
            'tables' => function ($query) {

                $query
                    ->withCount('columns')
                    ->orderBy(
                        'schema_name'
                    )
                    ->orderBy(
                        'name'
                    );
            }
        ]);


        return view(
            'database-discovery.show',
            compact(
                'discoveredDatabase'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public function table(
        DiscoveredTable $discoveredTable
    ) {

        $discoveredTable->load([
            'database',
            'columns'
        ]);


        return view(
            'database-discovery.table',
            compact(
                'discoveredTable'
            )
        );
    }
}