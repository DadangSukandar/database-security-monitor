<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\DatabaseConnectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConnectionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function prepareUserAndTeams(): array
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        return [$user, $teamA, $teamB];
    }

    private function createConnectionForTeam(
        Team $team,
        array $attributes = []
    ): DatabaseConnection {
        $connection = new DatabaseConnection(
            array_merge([
                'name' => 'Test Connection',
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'test_db',
                'username' => 'test_user',
                'password' => 'secret',
                'is_active' => true,
            ], $attributes)
        );

        $connection->team_id = $team->id;

        $connection->save();

        return $connection;
    }

    public function test_current_team_only_sees_its_database_connections(): void
    {
        [$user, $teamA, $teamB] = $this->prepareUserAndTeams();

        $this->createConnectionForTeam(
            $teamA,
            [
                'name' => 'Team A Connection',
            ]
        );

        $this->createConnectionForTeam(
            $teamB,
            [
                'name' => 'Team B Connection',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('database-connections.index'));

        $response
            ->assertOk()
            ->assertSee('Team A Connection')
            ->assertDontSee('Team B Connection');
    }

    public function test_cross_team_database_connection_show_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            [
                'name' => 'Foreign Connection',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'database-connections.show',
                    $connection
                )
            );

        $response->assertNotFound();
    }

    public function test_cross_team_database_connection_test_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            [
                'name' => 'Foreign Connection Test',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'database-connections.test',
                    $connection
                )
            );

        $response->assertNotFound();
    }

    public function test_cross_team_database_connection_scan_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            [
                'name' => 'Foreign Connection Scan',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'database-connections.scan',
                    $connection
                )
            );

        $response->assertNotFound();
    }

    public function test_cross_team_database_connection_destroy_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            [
                'name' => 'Foreign Connection Delete',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'database-connections.destroy',
                    $connection
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'database_connections',
            [
                'id' => $connection->id,
                'team_id' => $teamB->id,
            ]
        );
    }

    public function test_created_database_connection_inherits_current_team(): void
    {
        [$user, $teamA] = $this->prepareUserAndTeams();

        $connector = $this->mock(
            DatabaseConnectorService::class
        );

        $connector
            ->shouldReceive('test')
            ->once()
            ->andReturnTrue();

        $response = $this
            ->actingAs($user)
            ->post(
                route('database-connections.store'),
                [
                    'name' => 'Owned Connection',
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database' => 'owned_db',
                    'username' => 'owned_user',
                    'password' => 'secret',
                ]
            );

        $connection = DatabaseConnection::query()
            ->where('name', 'Owned Connection')
            ->firstOrFail();

        $this->assertSame(
            (int) $teamA->id,
            (int) $connection->team_id
        );

        $response->assertRedirect(
            route(
                'database-connections.show',
                $connection
            )
        );
    }
}
