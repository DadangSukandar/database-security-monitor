<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\DatabaseActivityLogger;
use App\Services\DatabaseConnectorService;
use App\Services\ReadOnlySqlGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SqlQueryController extends Controller
{
    public function index(): View
    {
        return view('sql-query.index', [
            'connections' => $this->activeConnections(),
        ]);
    }

    public function execute(
        Request $request,
        DatabaseConnectorService $connector,
        DatabaseActivityLogger $activityLogger,
        ReadOnlySqlGuard $readOnlySqlGuard,
    ): View|RedirectResponse {
        $validated = $request->validate([
            'database_connection_id' => [
                'required',
                'integer',
                'exists:database_connections,id',
            ],
            'query' => [
                'required',
                'string',
                'max:50000',
            ],
        ]);

        $databaseConnection = DatabaseConnection::query()->findOrFail(
            (int) $validated['database_connection_id']
        );

        $sql = trim((string) $validated['query']);
        $validationError = $readOnlySqlGuard->validationError(
            $sql,
            allowMetadataStatements: true,
        );

        if ($validationError !== null) {
            return back()
                ->withInput()
                ->withErrors(['query' => $validationError]);
        }

        try {
            $db = $connector->connect($databaseConnection);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'query' => 'Gagal terhubung ke database monitoring. Periksa konfigurasi koneksi dan log aplikasi.',
                ]);
        }

        $startTime = microtime(true);

        try {
            $rows = collect($db->select($sql))
                ->map(fn (object $row): array => (array) $row)
                ->values();

            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $columns = $rows->isNotEmpty()
                ? array_keys($rows->first())
                : [];

            $activityLogger->success(
                $databaseConnection,
                $sql,
                'SELECT',
                null,
                $executionTimeMs,
            );

            return view('sql-query.index', [
                'connections' => $this->activeConnections(),
                'selectedConnection' => $databaseConnection,
                'query' => $sql,
                'rows' => $rows,
                'columns' => $columns,
                'executionTimeMs' => $executionTimeMs,
                'resultCount' => $rows->count(),
            ]);
        } catch (Throwable $exception) {
            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            $activityLogger->failed(
                $databaseConnection,
                $sql,
                'SELECT',
                null,
                $exception,
                $executionTimeMs,
            );

            report($exception);

            return view('sql-query.index', [
                'connections' => $this->activeConnections(),
                'selectedConnection' => $databaseConnection,
                'query' => $sql,
                'rows' => collect(),
                'columns' => [],
                'executionTimeMs' => $executionTimeMs,
                'resultCount' => 0,
                'error' => 'Query tidak dapat dijalankan. Detail teknis telah dicatat di log aplikasi.',
            ]);
        }
    }

    private function activeConnections()
    {
        return DatabaseConnection::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
