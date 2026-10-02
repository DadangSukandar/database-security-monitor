<?php

use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\DatabaseDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('records last scanned time after a successful database discovery scan', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach(
        $user->id,
        ['role' => 'admin']
    );

    expect(
        $user->switchTeam($team)
    )->toBeTrue();

    $connection = new DatabaseConnection([
        'name' => 'Discovery Timestamp Target',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'discovery_timestamp_test',
        'username' => 'monitor',
        'password' => null,
        'schema' => null,
        'is_active' => true,
    ]);

    $connection->team_id = $team->id;
    $connection->save();

    $this->mock(
        DatabaseDiscoveryService::class,
        function (MockInterface $mock) use ($connection): void {
            $mock
                ->shouldReceive('scan')
                ->once()
                ->withArgs(
                    fn (DatabaseConnection $argument): bool => $argument->is($connection)
                )
                ->andReturn([
                    'database' => null,
                    'tables' => 0,
                    'columns' => 0,
                ]);
        }
    );

    $this
        ->actingAs($user)
        ->post(
            route(
                'database-discovery.scan',
                $connection
            )
        )
        ->assertRedirect(
            route('database-discovery.index')
        );

    expect(
        $connection
            ->refresh()
            ->last_scanned_at
    )->not->toBeNull();
});
