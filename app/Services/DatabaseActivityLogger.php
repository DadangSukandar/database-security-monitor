<?php

namespace App\Services;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use Throwable;
use App\Services\SecurityAlertService;

class DatabaseActivityLogger
{
    public function success(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table = null,
        ?int $executionTimeMs = null
    ): DatabaseActivity {

        return $this->write(
            connection: $connection,
            query: $query,
            action: $action,
            table: $table,
            status: 'success',
            executionTimeMs: $executionTimeMs
        );
    }

    public function failed(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table = null,
        ?Throwable $exception = null,
        ?int $executionTimeMs = null
    ): DatabaseActivity {

        return $this->write(
            connection: $connection,
            query: $query,
            action: $action,
            table: $table,
            status: 'failed',
            errorMessage: $exception?->getMessage(),
            executionTimeMs: $executionTimeMs
        );
    }

    private function write(
        DatabaseConnection $connection,
        string $query,
        string $action,
        ?string $table,
        string $status,
        ?string $errorMessage = null,
        ?int $executionTimeMs = null
    ): DatabaseActivity {

        $db = null;

        try {
            $db = app(
                DatabaseConnectorService::class
            )->connect($connection);
        } catch (Throwable) {
            // Jangan menggagalkan proses utama hanya
            // karena metadata connection gagal dibaca.
        }

        return DatabaseActivity::create([
            'database_connection_id' =>
                $connection->id,

            'database_name' =>
                $db?->getDatabaseName(),

            'schema_name' =>
                $this->getSchemaName(
                    $connection->driver
                ),

            'table_name' =>
                $table,

            'username' =>
                $connection->username,

            'client_ip' =>
                request()->ip(),

            'action' =>
                strtoupper($action),

            'query' =>
                $query,

            'status' =>
                $status,

            'error_message' =>
                $errorMessage,

            'execution_time_ms' =>
                $executionTimeMs,

            'executed_at' =>
                now(),
        ]);

        $this->securityAlertService->analyze($activity);
    }

    private function getSchemaName(
        string $driver
    ): ?string {

        if ($driver === 'pgsql') {
            return 'public';
        }

        return null;
    }

    public function __construct(
        SecurityAlertService $securityAlertService
    ) {
        $this->securityAlertService =
            $securityAlertService;
    }
}