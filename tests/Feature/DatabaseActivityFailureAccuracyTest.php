<?php

use App\Enums\DatabaseActivityStatus;
use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Services\DatabaseActivityLogger;
use App\Services\DatabaseConnectorService;
use App\Services\SecurityAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activityFailureConnection(): DatabaseConnection
{
    return DatabaseConnection::query()->create([
        'name' => 'Activity Failure Accuracy',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'activity_failure_test',
        'username' => 'monitor',
        'password' => null,
        'schema' => null,
        'is_active' => true,
    ]);
}

function activityFailureLogger(): DatabaseActivityLogger
{
    $alertService =
        Mockery::mock(
            SecurityAlertService::class
        );

    $alertService
        ->shouldReceive('analyze')
        ->once()
        ->with(
            Mockery::type(
                DatabaseActivity::class
            )
        );

    return new DatabaseActivityLogger(
        $alertService
    );
}

it('writes activity metadata without opening another target connection', function () {
    $connection =
        activityFailureConnection();

    $connector =
        Mockery::mock(
            DatabaseConnectorService::class
        );

    $connector
        ->shouldNotReceive(
            'withConnection'
        );

    app()->instance(
        DatabaseConnectorService::class,
        $connector
    );

    $activity =
        activityFailureLogger()->success(
            $connection,
            'SELECT * FROM users WHERE id = 12345',
            'SELECT',
            'users',
            25
        );

    expect(
        $activity->database_name
    )
        ->toBe(
            'activity_failure_test'
        )
        ->and(
            $activity->username
        )
        ->toBe(
            'monitor'
        )
        ->and(
            $activity->table_name
        )
        ->toBe(
            'users'
        )
        ->and(
            $activity->status
        )
        ->toBe(
            DatabaseActivityStatus::SUCCESS
        )
        ->and(
            $activity->query
        )
        ->toContain(
            'id = ?'
        )
        ->not->toContain(
            '12345'
        );
});

it('failed activity does not change connection health state', function () {
    $connection =
        activityFailureConnection();

    $connection->forceFill([
        'health_status' => DatabaseConnectionHealthStatus::UNHEALTHY,

        'consecutive_failures' => 1,

        'last_failure_type' => DatabaseConnectionFailureType::AUTHENTICATION_FAILED,

        'last_failed_at' => now(),
    ])->save();

    $originalLastFailedAt =
        $connection
            ->fresh()
            ->last_failed_at;

    $connector =
        Mockery::mock(
            DatabaseConnectorService::class
        );

    $connector
        ->shouldNotReceive(
            'withConnection'
        );

    app()->instance(
        DatabaseConnectorService::class,
        $connector
    );

    $activity =
        activityFailureLogger()->failed(
            $connection,
            "SELECT * FROM users WHERE token = 'SuperSecretToken'",
            'SELECT',
            'users',
            new RuntimeException(
                'SQLSTATE password=NeverStoreThis'
            ),
            50
        );

    $connection->refresh();

    expect(
        $connection->health_status
    )
        ->toBe(
            DatabaseConnectionHealthStatus::UNHEALTHY
        )
        ->and(
            $connection->consecutive_failures
        )
        ->toBe(1)
        ->and(
            $connection->last_failure_type
        )
        ->toBe(
            DatabaseConnectionFailureType::AUTHENTICATION_FAILED
        )
        ->and(
            $connection->last_failed_at?->equalTo(
                $originalLastFailedAt
            )
        )
        ->toBeTrue()
        ->and(
            $activity->status
        )
        ->toBe(
            DatabaseActivityStatus::FAILED
        )
        ->and(
            $activity->query
        )
        ->not->toContain(
            'SuperSecretToken'
        )
        ->and(
            $activity->error_message
        )
        ->toBe(
            'Database operation failed. Detail teknis tersedia di log aplikasi.'
        )
        ->not->toContain(
            'NeverStoreThis'
        );
});

it('failed activity without exception remains safely recordable', function () {
    $connection =
        activityFailureConnection();

    $activity =
        activityFailureLogger()->failed(
            $connection,
            'SELECT 1',
            'SELECT',
            null,
            null,
            5
        );

    expect(
        $activity->status
    )
        ->toBe(
            DatabaseActivityStatus::FAILED
        )
        ->and(
            $activity->error_message
        )
        ->toBeNull();
});
