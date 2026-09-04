<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectionHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->connection =
        DatabaseConnection::query()->create([
            'name' => 'Health Test Database',

            'driver' => 'mysql',

            'host' => '127.0.0.1',

            'port' => 3306,

            'database' => 'health_test',

            'username' => 'monitor',

            'password' => null,

            'schema' => null,

            'is_active' => true,
        ]);

    $this->connection->refresh();

    $this->healthService =
        app(
            DatabaseConnectionHealthService::class
        );
});

it('starts a database connection with unknown health', function () {
    expect(
        $this->connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::UNKNOWN
        )
        ->and(
            $this->connection
                ->consecutive_failures
        )
        ->toBe(0)
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBeNull()
        ->and(
            $this->connection
                ->last_health_checked_at
        )
        ->toBeNull();
});

it('marks a successful connection as healthy', function () {
    $this->healthService->markHealthy(
        $this->connection
    );

    $this->connection->refresh();

    expect(
        $this->connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::HEALTHY
        )
        ->and(
            $this->connection
                ->last_connected_at
        )
        ->not->toBeNull()
        ->and(
            $this->connection
                ->last_health_checked_at
        )
        ->not->toBeNull()
        ->and(
            $this->connection
                ->consecutive_failures
        )
        ->toBe(0)
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBeNull()
        ->and(
            $this->connection
                ->last_recovered_at
        )
        ->toBeNull();
});

it('marks a connection failure as unhealthy', function () {
    $this->healthService->markUnhealthy(
        $this->connection,
        DatabaseConnectionFailureType::TIMEOUT
    );

    $this->connection->refresh();

    expect(
        $this->connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::UNHEALTHY
        )
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBe(
            DatabaseConnectionFailureType::TIMEOUT
        )
        ->and(
            $this->connection
                ->consecutive_failures
        )
        ->toBe(1)
        ->and(
            $this->connection
                ->last_failed_at
        )
        ->not->toBeNull()
        ->and(
            $this->connection
                ->last_health_checked_at
        )
        ->not->toBeNull();
});

it('increments consecutive connection failures', function () {
    $this->healthService->markUnhealthy(
        $this->connection,
        DatabaseConnectionFailureType::TIMEOUT
    );

    $this->healthService->markUnhealthy(
        $this->connection,
        DatabaseConnectionFailureType::HOST_UNREACHABLE
    );

    $this->connection->refresh();

    expect(
        $this->connection
            ->consecutive_failures
    )
        ->toBe(2)
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBe(
            DatabaseConnectionFailureType::HOST_UNREACHABLE
        );
});

it('records recovery after an unhealthy connection becomes healthy', function () {
    $this->healthService->markUnhealthy(
        $this->connection,
        DatabaseConnectionFailureType::AUTHENTICATION_FAILED
    );

    $this->healthService->markHealthy(
        $this->connection
    );

    $this->connection->refresh();

    expect(
        $this->connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::HEALTHY
        )
        ->and(
            $this->connection
                ->consecutive_failures
        )
        ->toBe(0)
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBeNull()
        ->and(
            $this->connection
                ->last_recovered_at
        )
        ->not->toBeNull()
        ->and(
            $this->connection
                ->last_failed_at
        )
        ->not->toBeNull();
});

it('increments failures correctly from stale model instances', function () {
    $firstSnapshot =
        DatabaseConnection::query()
            ->findOrFail(
                $this->connection->id
            );

    $secondSnapshot =
        DatabaseConnection::query()
            ->findOrFail(
                $this->connection->id
            );

    /*
    |--------------------------------------------------------------------------
    | Failure Worker A
    |--------------------------------------------------------------------------
    */

    $this->healthService->markUnhealthy(
        $firstSnapshot,
        DatabaseConnectionFailureType::TIMEOUT
    );

    /*
    |--------------------------------------------------------------------------
    | Failure Worker B
    |--------------------------------------------------------------------------
    |
    | secondSnapshot dibuat sebelum failure pertama.
    | Service tidak boleh mempercayai counter dari snapshot lama.
    |
    */

    $this->healthService->markUnhealthy(
        $secondSnapshot,
        DatabaseConnectionFailureType::HOST_UNREACHABLE
    );

    $this->connection->refresh();

    expect(
        $this->connection
            ->consecutive_failures
    )
        ->toBe(2)
        ->and(
            $this->connection
                ->last_failure_type
        )
        ->toBe(
            DatabaseConnectionFailureType::HOST_UNREACHABLE
        )
        ->and(
            $this->connection->health_status
        )
        ->toBe(
            DatabaseConnectionHealthStatus::UNHEALTHY
        );
});

it('does not create another recovery timestamp for repeated healthy checks', function () {
    $this->healthService->markUnhealthy(
        $this->connection,
        DatabaseConnectionFailureType::TIMEOUT
    );

    $this->healthService->markHealthy(
        $this->connection
    );

    $this->connection->refresh();

    $firstRecoveredAt =
        $this->connection
            ->last_recovered_at
            ?->copy();

    expect(
        $firstRecoveredAt
    )->not->toBeNull();

    /*
    |--------------------------------------------------------------------------
    | Sudah HEALTHY
    |--------------------------------------------------------------------------
    |
    | Health check berikutnya bukan recovery baru.
    |
    */

    $this->healthService->markHealthy(
        $this->connection
    );

    $this->connection->refresh();

    expect(
        $this->connection
            ->last_recovered_at
            ?->equalTo(
                $firstRecoveredAt
            )
    )->toBeTrue();
});
