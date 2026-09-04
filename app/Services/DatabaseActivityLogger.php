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
        /*
        |--------------------------------------------------------------------------
        | Exception Ownership
        |--------------------------------------------------------------------------
        |
        | Logger activity tidak melakukan report() terhadap exception operasi.
        |
        | Caller tetap menjadi pemilik lifecycle exception dan menentukan
        | apakah exception harus dilaporkan, dilempar ulang, atau diterjemahkan
        | menjadi response yang aman.
        |
        | Dengan demikian exception yang sama tidak dilaporkan dua kali hanya
        | karena activity audit ikut dicatat.
        |
        */

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
        /*
        |--------------------------------------------------------------------------
        | Query Redaction Boundary
        |--------------------------------------------------------------------------
        */

        $safeQuery = app(
            DatabaseActivityQuerySanitizer::class
        )->sanitize(
            $query,
            $connection->driver
        );

        /*
        |--------------------------------------------------------------------------
        | Activity Metadata
        |--------------------------------------------------------------------------
        |
        | Jangan membuka koneksi target hanya untuk mengambil nama database.
        |
        | DatabaseConnection sudah merupakan source of truth untuk target
        | yang sedang dimonitor.
        |
        | Ini membuat activity logging health-neutral dan tidak menambah
        | connection attempt ketika target sedang gagal.
        |
        */

        $activity = DatabaseActivity::query()->create([
            'database_connection_id' => $connection->id,
            'database_name' => $connection->database,
            'schema_name' => $this->getSchemaName(
                $connection->driver
            ),
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

        /*
        |--------------------------------------------------------------------------
        | Security Analysis
        |--------------------------------------------------------------------------
        |
        | analyze() adalah side effect setelah activity tersimpan.
        | Kegagalan analisis tidak boleh menghilangkan activity audit.
        |
        */

        try {
            $this->securityAlertService->analyze(
                $activity
            );
        } catch (Throwable $exception) {
            report(
                $exception
            );
        }

        return $activity;
    }

    private function getSchemaName(
        string $driver
    ): ?string {
        if ($driver === 'pgsql') {
            return 'public';
        }

        return null;
    }
}
