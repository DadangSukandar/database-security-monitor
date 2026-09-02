<?php

namespace App\Http\Controllers;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Services\DatabaseActivityLogger;
use App\Services\DatabaseConnectorService;
use App\Services\ReadOnlySqlGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DatabaseQueryController extends Controller
{
    public function index(): View
    {
        $connections = DatabaseConnection::query()
            ->orderBy('name')
            ->get();

        $history = DatabaseActivity::query()
            ->where('action', 'QUERY')
            ->with('databaseConnection')
            ->latest('executed_at')
            ->paginate(20);

        return view('database-query.index', compact('connections', 'history'));
    }

    public function execute(
        Request $request,
        DatabaseConnectorService $connector,
        DatabaseActivityLogger $activityLogger,
        ReadOnlySqlGuard $readOnlySqlGuard,
    ): View|RedirectResponse {
        $validated = $request->validate([
            'connection_id' => [
                'required',
                'integer',
                'exists:database_connections,id',
            ],
            'sql' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        $connection = DatabaseConnection::query()->findOrFail(
            (int) $validated['connection_id']
        );

        $sql = rtrim(trim((string) $validated['sql']), " \t\n\r\0\x0B;");
        $validationError = $readOnlySqlGuard->validationError($sql);

        if ($validationError !== null) {
            return back()
                ->withErrors(['sql' => $validationError])
                ->withInput();
        }

        $startTime = microtime(true);

        try {
            $db = $connector->connect($connection);
            $rows = array_map(
                fn (object $row): array => (array) $row,
                $db->select($sql),
            );

            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $totalRows = count($rows);
            $displayRows = array_slice($rows, 0, 500);

            $activityLogger->success(
                $connection,
                $sql,
                'QUERY',
                null,
                $executionTimeMs,
            );

            return view('database-query.result', compact(
                'connection',
                'sql',
                'displayRows',
                'totalRows',
                'executionTimeMs',
            ));
        } catch (Throwable $exception) {
            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            $activityLogger->failed(
                $connection,
                $sql,
                'QUERY',
                null,
                $exception,
                $executionTimeMs,
            );

            report($exception);

            return back()
                ->withErrors([
                    'sql' => 'Query tidak dapat dijalankan. Detail teknis telah dicatat di log aplikasi.',
                ])
                ->withInput();
        }
    }
}
