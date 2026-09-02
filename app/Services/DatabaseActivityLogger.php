<?php

namespace App\Services;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use Throwable;

class DatabaseActivityLogger
{
    public function __construct(
        private readonly SecurityAlertService $securityAlertService,
    ) {}

    public function success(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table = null,
        ?int $executionTimeMs = null,
    ): DatabaseActivity {
        return $this->write(
            connection: $connection,
            query: $query,
            action: $action,
            table: $table,
            status: 'success',
            executionTimeMs: $executionTimeMs,
        );
    }

    public function failed(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table = null,
        ?Throwable $exception = null,
        ?int $executionTimeMs = null,
    ): DatabaseActivity {
        if ($exception !== null) {
            report($exception);
        }

        return $this->write(
            connection: $connection,
            query: $query,
            action: $action,
            table: $table,
            status: 'failed',
            errorMessage: $exception === null
                ? null
                : 'Database operation failed. Detail teknis tersedia di log aplikasi.',
            executionTimeMs: $executionTimeMs,
        );
    }

    private function write(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table,
        string $status,
        ?string $errorMessage = null,
        ?int $executionTimeMs = null,
    ): DatabaseActivity {
        $db = null;

        try {
            $db = app(DatabaseConnectorService::class)->connect($connection);
        } catch (Throwable $exception) {
            report($exception);
        }

        $activity = DatabaseActivity::query()->create([
            'database_connection_id' => $connection->id,
            'database_name' => $db?->getDatabaseName(),
            'schema_name' => $this->getSchemaName($connection->driver),
            'table_name' => $table,
            'username' => $connection->username,
            'client_ip' => request()->ip(),
            'action' => strtoupper($action),
            'query' => $query,
            'status' => $status,
            'error_message' => $errorMessage,
            'execution_time_ms' => $executionTimeMs,
            'executed_at' => now(),
        ]);

        try {
            $this->securityAlertService->analyze($activity);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $activity;
    }

    private function getSchemaName(string $driver): ?string
    {
        if ($driver === 'pgsql') {
            return 'public';
        }

        return null;
    }
}
