<?php

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('normalizes uppercase database activity status filters', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach(
        $user->id,
        ['role' => 'admin']
    );

    expect($user->switchTeam($team))
        ->toBeTrue();

    $connection = new DatabaseConnection([
        'name' => 'Activity Filter Target',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'activity_filter_test',
        'username' => 'monitor',
        'password' => null,
        'is_active' => true,
    ]);

    $connection->team_id = $team->id;
    $connection->save();

    foreach ([
        ['status' => 'success', 'query' => 'SELECT 1'],
        ['status' => 'failed', 'query' => 'SELECT broken'],
    ] as $data) {
        $activity = new DatabaseActivity([
            'database_connection_id' => $connection->id,
            'database_name' => $connection->database,
            'username' => $connection->username,
            'action' => 'SELECT',
            'query' => $data['query'],
            'status' => $data['status'],
            'execution_time_ms' => 1,
            'executed_at' => now(),
        ]);

        $activity->team_id = $team->id;
        $activity->save();
    }

    $this
        ->actingAs($user)
        ->get(route('database-activities.index', [
            'status' => 'SUCCESS',
        ]))
        ->assertOk()
        ->assertViewHas(
            'activities',
            fn ($activities) => $activities->total() === 1
                && $activities->first()->status->value === 'success'
        );

    $this
        ->actingAs($user)
        ->get(route('database-activities.index', [
            'status' => 'FAILED',
        ]))
        ->assertOk()
        ->assertViewHas(
            'activities',
            fn ($activities) => $activities->total() === 1
                && $activities->first()->status->value === 'failed'
        );
});
