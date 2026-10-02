<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredColumn;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
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

    public function index(
        Request $request
    ) {
        $teamId =
            (int) $request->user()->current_team_id;

        $databases =
            DiscoveredDatabase::query()
                ->whereHas(
                    'databaseConnection',
                    function ($query) use ($teamId): void {
                        $query->forTeam(
                            $teamId
                        );
                    }
                )
                ->withCount('tables')
                ->with('databaseConnection')
                ->latest()
                ->get();

        $connections =
            DatabaseConnection::query()
                ->forTeam($teamId)
                ->orderBy('name')
                ->get();

        $totalDatabases =
            $databases->count();

        $totalTables =
            DiscoveredTable::query()
                ->whereHas(
                    'database.databaseConnection',
                    function ($query) use ($teamId): void {
                        $query->forTeam(
                            $teamId
                        );
                    }
                )
                ->count();

        $totalColumns =
            DiscoveredColumn::query()
                ->whereHas(
                    'table.database.databaseConnection',
                    function ($query) use ($teamId): void {
                        $query->forTeam(
                            $teamId
                        );
                    }
                )
                ->count();

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
        Request $request,
        DatabaseConnection $databaseConnection,
        DatabaseDiscoveryService $service
    ) {
        $this->ensureConnectionBelongsToCurrentTeam(
            $request,
            $databaseConnection
        );

        try {
            $result =
                $service->scan(
                    $databaseConnection
                );

            $databaseConnection->update([
                'last_scanned_at' => now(),
            ]);

            return redirect()
                ->route(
                    'database-discovery.index'
                )
                ->with(
                    'success',
                    'Discovery selesai. '.
                    $result['tables'].
                    ' tables dan '.
                    $result['columns'].
                    ' columns ditemukan.'
                );
        } catch (Throwable $e) {
            return redirect()
                ->route(
                    'database-discovery.index'
                )
                ->with(
                    'error',
                    'Discovery gagal: '.
                    $this->safeExceptionDetail($e)
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW DATABASE
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        DiscoveredDatabase $discoveredDatabase
    ) {
        $this->ensureDiscoveredDatabaseBelongsToCurrentTeam(
            $request,
            $discoveredDatabase
        );

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
            },
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
        Request $request,
        DiscoveredTable $discoveredTable
    ) {
        $this->ensureDiscoveredTableBelongsToCurrentTeam(
            $request,
            $discoveredTable
        );

        $discoveredTable->load([
            'database.databaseConnection',
            'columns',
        ]);

        return view(
            'database-discovery.table',
            compact(
                'discoveredTable'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TENANT GUARDS
    |--------------------------------------------------------------------------
    */

    private function ensureConnectionBelongsToCurrentTeam(
        Request $request,
        DatabaseConnection $databaseConnection
    ): void {
        $teamId =
            $request->user()?->current_team_id;

        abort_if(
            $teamId === null ||
            (int) $databaseConnection->team_id
                !== (int) $teamId,
            404
        );
    }

    private function ensureDiscoveredDatabaseBelongsToCurrentTeam(
        Request $request,
        DiscoveredDatabase $discoveredDatabase
    ): void {
        $teamId =
            $request->user()?->current_team_id;

        abort_if(
            $teamId === null,
            404
        );

        $belongsToTeam =
            DatabaseConnection::query()
                ->forTeam(
                    (int) $teamId
                )
                ->whereKey(
                    $discoveredDatabase
                        ->database_connection_id
                )
                ->exists();

        abort_unless(
            $belongsToTeam,
            404
        );
    }

    private function ensureDiscoveredTableBelongsToCurrentTeam(
        Request $request,
        DiscoveredTable $discoveredTable
    ): void {
        $teamId =
            $request->user()?->current_team_id;

        abort_if(
            $teamId === null,
            404
        );

        $belongsToTeam =
            DiscoveredTable::query()
                ->whereKey(
                    $discoveredTable->id
                )
                ->whereHas(
                    'database.databaseConnection',
                    function ($query) use ($teamId): void {
                        $query->forTeam(
                            (int) $teamId
                        );
                    }
                )
                ->exists();

        abort_unless(
            $belongsToTeam,
            404
        );
    }
}
