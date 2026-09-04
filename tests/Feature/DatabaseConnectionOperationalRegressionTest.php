<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use App\Exceptions\DatabaseConnectionException;
use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectorService;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function operationalDatabaseConnection(): DatabaseConnection
{
    return DatabaseConnection::query()->create([
        'name' => 'Operational Regression',

        'driver' => 'mysql',

        'host' => '127.0.0.1',

        'port' => 3306,

        'database' => 'operational_test',

        'username' => 'monitor',

        'password' => null,

        'schema' => null,

        'is_active' => true,
    ]);
}

it('completes a successful connection test and records healthy state', function () {
    $connection =
        operationalDatabaseConnection();

    $runtime =
        Mockery::mock(
            Connection::class
        );

    $runtime
        ->shouldReceive(
            'statement'
        )
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
        ->shouldReceive(
            'connectionFromData'
        )
        ->once()
        ->andReturn(
            $runtime
        );

    $connector
        ->shouldReceive(
            'release'
        )
        ->once()
        ->with(
            $runtime
        );

    expect(
        $connector->test(
            $connection
        )
    )->toBeTrue();

    $connection->refresh();

    expect(
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

it('records a safe unhealthy state when connection establishment fails', function () {
    $connection =
        operationalDatabaseConnection();

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
        ->andThrow(
            new RuntimeException(
                'SQLSTATE access denied '.
                'user=secret-monitor '.
                'password=NeverExposeThis'
            )
        );

    try {
        $connector->test(
            $connection
        );

        test()->fail(
            'DatabaseConnectionException was not thrown.'
        );
    } catch (
        DatabaseConnectionException $exception
    ) {
        expect(
            $exception->failureType
        )
            ->toBe(
                DatabaseConnectionFailureType::AUTHENTICATION_FAILED
            )
            ->and(
                $exception->getPrevious()
            )
            ->toBeNull()
            ->and(
                $exception->getMessage()
            )
            ->toBe(
                'Database monitoring connection failed.'
            )
            ->and(
                (string) $exception
            )
            ->not->toContain(
                'NeverExposeThis'
            )
            ->not->toContain(
                'secret-monitor'
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
            DatabaseConnectionFailureType::AUTHENTICATION_FAILED
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

it('records recovery after a failed connection later succeeds', function () {
    $connection =
        operationalDatabaseConnection();

    /*
    |--------------------------------------------------------------------------
    | Initial Failure
    |--------------------------------------------------------------------------
    */

    $failedConnector =
        Mockery::mock(
            DatabaseConnectorService::class
        )
            ->makePartial();

    $failedConnector
        ->shouldReceive(
            'connectionFromData'
        )
        ->once()
        ->andThrow(
            new RuntimeException(
                'connection refused'
            )
        );

    try {
        $failedConnector->test(
            $connection
        );
    } catch (
        DatabaseConnectionException
    ) {
        //
    }

    $connection->refresh();

    expect(
        $connection->health_status
    )->toBe(
        DatabaseConnectionHealthStatus::UNHEALTHY
    );

    /*
    |--------------------------------------------------------------------------
    | Recovery
    |--------------------------------------------------------------------------
    */

    $runtime =
        Mockery::mock(
            Connection::class
        );

    $runtime
        ->shouldReceive(
            'statement'
        )
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
        ->shouldReceive(
            'connectionFromData'
        )
        ->once()
        ->andReturn(
            $runtime
        );

    $healthyConnector
        ->shouldReceive(
            'release'
        )
        ->once()
        ->with(
            $runtime
        );

    expect(
        $healthyConnector->test(
            $connection
        )
    )->toBeTrue();

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

it('keeps connection healthy when the scoped operation fails after connecting', function () {
    $connection =
        operationalDatabaseConnection();

    $runtime =
        Mockery::mock(
            Connection::class
        );

    $runtime
        ->shouldReceive(
            'statement'
        )
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
        ->shouldReceive(
            'connectionFromData'
        )
        ->once()
        ->andReturn(
            $runtime
        );

    $connector
        ->shouldReceive(
            'release'
        )
        ->once()
        ->with(
            $runtime
        );

    try {
        $connector->withConnection(
            $connection,
            function (): void {
                throw new RuntimeException(
                    'Query operation failed.'
                );
            }
        );

        test()->fail(
            'RuntimeException was not thrown.'
        );
    } catch (
        RuntimeException $exception
    ) {
        expect(
            $exception->getMessage()
        )->toBe(
            'Query operation failed.'
        );
    }

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
            $connection->last_failed_at
        )
        ->toBeNull();
});

it('releases the runtime connection and records read only setup failure', function () {
    $connection =
        operationalDatabaseConnection();

    $runtime =
        Mockery::mock(
            Connection::class
        );

    $runtime
        ->shouldReceive(
            'statement'
        )
        ->once()
        ->with(
            'SET SESSION TRANSACTION READ ONLY'
        )
        ->andThrow(
            new RuntimeException(
                'read only session setup failed'
            )
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
            $runtime
        );

    $connector
        ->shouldReceive(
            'release'
        )
        ->once()
        ->with(
            $runtime
        );

    try {
        $connector->connect(
            $connection
        );

        test()->fail(
            'DatabaseConnectionException was not thrown.'
        );
    } catch (
        DatabaseConnectionException $exception
    ) {
        expect(
            $exception->failureType
        )->toBe(
            DatabaseConnectionFailureType::READ_ONLY_SETUP_FAILED
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
            DatabaseConnectionFailureType::READ_ONLY_SETUP_FAILED
        )
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(1);
});
