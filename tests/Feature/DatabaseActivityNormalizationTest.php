<?php

use App\Enums\DatabaseActivityStatus;
use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activityNormalizationConnection(): DatabaseConnection
{
    return DatabaseConnection::query()->create([
        'name' => 'Activity Normalization',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'activity_test',
        'username' => 'monitor',
        'password' => null,
        'schema' => null,
        'is_active' => true,
    ]);
}

it('casts successful activity status to the canonical enum', function () {
    $connection =
        activityNormalizationConnection();

    $activity =
        DatabaseActivity::query()->create([
            'database_connection_id' => $connection->id,

            'database_name' => $connection->database,

            'username' => $connection->username,

            'action' => 'SELECT',

            'query' => 'SELECT 1',

            'status' => DatabaseActivityStatus::SUCCESS->value,

            'executed_at' => now(),
        ]);

    expect(
        $activity->status
    )->toBe(
        DatabaseActivityStatus::SUCCESS
    );
});

it('casts failed activity status to the canonical enum', function () {
    $connection =
        activityNormalizationConnection();

    $activity =
        DatabaseActivity::query()->create([
            'database_connection_id' => $connection->id,

            'database_name' => $connection->database,

            'username' => $connection->username,

            'action' => 'SELECT',

            'query' => 'SELECT 1',

            'status' => DatabaseActivityStatus::FAILED->value,

            'executed_at' => now(),
        ]);

    expect(
        $activity->status
    )->toBe(
        DatabaseActivityStatus::FAILED
    );
});

it('uses lowercase canonical database values', function () {
    expect(
        DatabaseActivityStatus::SUCCESS->value
    )
        ->toBe('success')
        ->and(
            DatabaseActivityStatus::FAILED->value
        )
        ->toBe('failed');
});
