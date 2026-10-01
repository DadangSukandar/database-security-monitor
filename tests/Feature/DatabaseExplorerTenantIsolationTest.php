<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\DatabaseConnectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseExplorerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_team_database_connection_is_rejected_before_connector(): void
    {
        $teamA =
            Team::factory()->create();

        $teamB =
            Team::factory()->create();

        $user =
            User::factory()->create();

        $teamA->members()->attach(
            $user->id,
            [
                'role' => 'admin',
            ]
        );

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $this->actingAs(
            $user->refresh()
        );

        $connection =
            new DatabaseConnection([
                'name' => 'Team B Explorer Database',
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'team_b_explorer',
                'username' => 'test_user',
                'password' => null,
                'is_active' => true,
            ]);

        $connection->team_id =
            $teamB->id;

        $connection->save();

        $connector =
            $this->mock(
                DatabaseConnectorService::class
            );

        $connector->shouldNotReceive(
            'withConnection'
        );

        $this->get(
            route(
                'database-explorer.show',
                [
                    $connection,
                    'users',
                ]
            )
        )
            ->assertNotFound();
    }
}
