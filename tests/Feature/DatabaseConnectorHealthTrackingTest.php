<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use App\Exceptions\DatabaseConnectionException;
use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectorService;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function healthTrackingConnection(): DatabaseConnection
{
    return DatabaseConnection::query()->create([
        'name' => 'Health Tracking Test',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'health_tracking',
        'username' => 'monitor',
        'password' => null,
        'schema' => null,
        'is_active' => true,
    ]);
}

it('marks a successful connector operation as healthy', function () {
    $connection = healthTrackingConnection();

    $runtimeConnection =
        Mockery::mock(Connection::class);

    $runtimeConnection
        ->shouldReceive('statement')
        ->once()
        ->with(
            'SET SESSION TRANSACTION READ ONLY'
        )
        ->andReturnTrue();

    $connector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $connector
        ->shouldReceive('connectionFromData')
        ->once()
        ->andReturn(
            $runtimeConnection
        );

    $result =
        $connector->connect(
            $connection
        );

    $connection->refresh();

    expect($result)
        ->toBe($runtimeConnection)
        ->and(
            $connection->health_status
        )
        ->toBe(
            DatabaseConnectionHealthStatus::HEALTHY
        )
        ->and(
            $connection->last_connected_at
        )
        ->not->toBeNull()
        ->and(
            $connection->last_health_checked_at
        )
        ->not->toBeNull()
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(0);
});

it('marks a connection establishment failure as unhealthy', function () {
    $connection = healthTrackingConnection();

    $connector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $connector
        ->shouldReceive('connectionFromData')
        ->once()
        ->andThrow(
            new RuntimeException(
                'connection timed out'
            )
        );

    try {
        $connector->connect(
            $connection
        );

        test()->fail(
            'DatabaseConnectionException was not thrown.'
        );
    } catch (DatabaseConnectionException $exception) {
        expect(
            $exception->failureType
        )->toBe(
            DatabaseConnectionFailureType::TIMEOUT
        );
    }

    $connection->refresh();

    expect(
        $connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::UNHEALTHY
        )
        ->and(
            $connection->last_failure_type
        )
        ->toBe(
            DatabaseConnectionFailureType::TIMEOUT
        )
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(1)
        ->and(
            $connection->last_failed_at
        )
        ->not->toBeNull();
});

it('records recovery when a failed connection later succeeds', function () {
    $connection = healthTrackingConnection();

    $failedConnector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $failedConnector
        ->shouldReceive('connectionFromData')
        ->once()
        ->andThrow(
            new RuntimeException(
                'connection refused'
            )
        );

    try {
        $failedConnector->connect(
            $connection
        );
    } catch (DatabaseConnectionException) {
        //
    }

    $connection->refresh();

    expect(
        $connection->health_status
    )->toBe(
        DatabaseConnectionHealthStatus::UNHEALTHY
    );

    $runtimeConnection =
        Mockery::mock(Connection::class);

    $runtimeConnection
        ->shouldReceive('statement')
        ->once()
        ->with(
            'SET SESSION TRANSACTION READ ONLY'
        )
        ->andReturnTrue();

    $healthyConnector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $healthyConnector
        ->shouldReceive('connectionFromData')
        ->once()
        ->andReturn(
            $runtimeConnection
        );

    $healthyConnector->connect(
        $connection
    );

    $connection->refresh();

    expect(
        $connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::HEALTHY
        )
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(0)
        ->and(
            $connection->last_failure_type
        )
        ->toBeNull()
        ->and(
            $connection->last_recovered_at
        )
        ->not->toBeNull()
        ->and(
            $connection->last_failed_at
        )
        ->not->toBeNull();
});

it('does not mark the connection unhealthy when an operation fails after connecting', function () {
    $connection = healthTrackingConnection();

    $runtimeConnection =
        Mockery::mock(Connection::class);

    $runtimeConnection
        ->shouldReceive('statement')
        ->once()
        ->with(
            'SET SESSION TRANSACTION READ ONLY'
        )
        ->andReturnTrue();

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | Nama non-monitoring cukup untuk unit test ini.
    | release() akan mengabaikannya.
    |
    */

    $runtimeConnection
        ->shouldReceive('getName')
        ->once()
        ->andReturn(
            'health_operation_test'
        );

    $connector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $connector
        ->shouldReceive(
            'connectionFromData'
        )
        ->once()
        ->andReturn(
            $runtimeConnection
        );

    try {
        $connector->withConnection(
            $connection,
            function (): void {
                /*
                |--------------------------------------------------------------------------
                | Operation Failure
                |--------------------------------------------------------------------------
                |
                | Koneksi sudah berhasil.
                | Error ini mensimulasikan query / operasi setelah connect.
                |
                */

                throw new RuntimeException(
                    'operation failed'
                );
            }
        );

        test()->fail(
            'RuntimeException was not thrown.'
        );
    } catch (RuntimeException $exception) {
        expect(
            $exception->getMessage()
        )->toBe(
            'operation failed'
        );
    }

    $connection->refresh();

    /*
    |--------------------------------------------------------------------------
    | Connection Tetap Healthy
    |--------------------------------------------------------------------------
    */

    expect(
        $connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::HEALTHY
        )
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(0)
        ->and(
            $connection->last_failure_type
        )
        ->toBeNull()
        ->and(
            $connection->last_failed_at
        )
        ->toBeNull()
        ->and(
            $connection->last_connected_at
        )
        ->not->toBeNull();
});
