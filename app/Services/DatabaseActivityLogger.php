<?php

namespace App\Services;

use App\Enums\DatabaseActivityStatus;
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
            status: DatabaseActivityStatus::SUCCESS,
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
            status: DatabaseActivityStatus::FAILED,
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
        DatabaseActivityStatus $status,
        ?string $errorMessage = null,
        ?int $executionTimeMs = null,
    ): DatabaseActivity {
        $databaseName = null;
        $safeQuery =
        app(
            DatabaseActivityQuerySanitizer::class
        )->sanitize(
            $query,
            $connection->driver
        );

        try {
            $databaseName = app(
                DatabaseConnectorService::class
            )->withConnection(
                $connection,
                fn ($db) => $db->getDatabaseName()
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        $activity = DatabaseActivity::query()->create([
            'database_connection_id' => $connection->id,
            'database_name' => $databaseName,
            'schema_name' => $this->getSchemaName($connection->driver),
            'table_name' => $table,
            'username' => $connection->username,
            'client_ip' => request()->ip(),
            'action' => strtoupper($action),
            'query' => $safeQuery,
            'status' => $status->value,
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
