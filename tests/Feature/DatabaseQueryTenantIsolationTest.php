<?php

namespace Tests\Feature;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\DatabaseConnectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseQueryTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_database_query_index_only_contains_current_team_connections_and_history(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $teamAConnection =
            $this->createConnectionForTeam(
                $this->team,
                'Team A Query Database',
                'team_a_query_database'
            );

        $teamBConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Team B Hidden Database',
                'team_b_hidden_database'
            );

        $teamAActivity =
            $this->createActivityForTeam(
                $this->team,
                $teamAConnection,
                [
                    'query' => 'SELECT * FROM team_a_users',
                    'action' => 'QUERY',
                ]
            );

        $teamBActivity =
            $this->createActivityForTeam(
                $otherTeam,
                $teamBConnection,
                [
                    'query' => 'SELECT * FROM team_b_secret',
                    'action' => 'QUERY',
                ]
            );

        $response =
            $this->get(
                route('database-query.index')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'connections',
                function ($connections) use (
                    $teamAConnection,
                    $teamBConnection
                ): bool {
                    return $connections->contains(
                        'id',
                        $teamAConnection->id
                    )
                        && ! $connections->contains(
                            'id',
                            $teamBConnection->id
                        );
                }
            )
            ->assertViewHas(
                'history',
                function ($history) use (
                    $teamAActivity,
                    $teamBActivity
                ): bool {
                    $items =
                        method_exists(
                            $history,
                            'items'
                        )
                            ? collect(
                                $history->items()
                            )
                            : collect($history);

                    return $items->contains(
                        'id',
                        $teamAActivity->id
                    )
                        && ! $items->contains(
                            'id',
                            $teamBActivity->id
                        );
                }
            );
    }

    public function test_database_query_execute_rejects_cross_team_connection_before_connector(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $foreignConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign Query Database',
                'foreign_query_database'
            );

        $connector =
            $this->mock(
                DatabaseConnectorService::class
            );

        $connector->shouldNotReceive(
            'withConnection'
        );

        $this->post(
            route('database-query.execute'),
            [
                'connection_id' => $foreignConnection->id,

                'sql' => 'SELECT 1',
            ]
        )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'database_activities',
            [
                'team_id' => $this->team->id,

                'database_connection_id' => $foreignConnection->id,
            ]
        );
    }

    public function test_query_history_index_only_contains_current_team_activities_and_connections(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $teamAConnection =
            $this->createConnectionForTeam(
                $this->team,
                'Team A History Database',
                'team_a_history_database'
            );

        $teamBConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Team B History Database',
                'team_b_history_database'
            );

        $teamAActivity =
            $this->createActivityForTeam(
                $this->team,
                $teamAConnection,
                [
                    'query' => 'SELECT * FROM team_a_history',
                ]
            );

        $teamBActivity =
            $this->createActivityForTeam(
                $otherTeam,
                $teamBConnection,
                [
                    'query' => 'SELECT * FROM team_b_history',
                ]
            );

        $response =
            $this->get(
                route('query-history.index')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'connections',
                function ($connections) use (
                    $teamAConnection,
                    $teamBConnection
                ): bool {
                    return $connections->contains(
                        'id',
                        $teamAConnection->id
                    )
                        && ! $connections->contains(
                            'id',
                            $teamBConnection->id
                        );
                }
            )
            ->assertViewHas(
                'activities',
                function ($activities) use (
                    $teamAActivity,
                    $teamBActivity
                ): bool {
                    $items =
                        method_exists(
                            $activities,
                            'items'
                        )
                            ? collect(
                                $activities->items()
                            )
                            : collect($activities);

                    return $items->contains(
                        'id',
                        $teamAActivity->id
                    )
                        && ! $items->contains(
                            'id',
                            $teamBActivity->id
                        );
                }
            );
    }

    public function test_current_team_can_view_own_query_history_activity(): void
    {
        $this->actingAsTeamAdmin();

        $connection =
            $this->createConnectionForTeam(
                $this->team,
                'Owned History Database',
                'owned_history_database'
            );

        $activity =
            $this->createActivityForTeam(
                $this->team,
                $connection,
                [
                    'query' => 'SELECT * FROM owned_history',
                ]
            );

        $this->get(
            route(
                'query-history.show',
                $activity
            )
        )
            ->assertOk();
    }

    public function test_cross_team_query_history_detail_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $foreignConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign History Database',
                'foreign_history_database'
            );

        $foreignActivity =
            $this->createActivityForTeam(
                $otherTeam,
                $foreignConnection,
                [
                    'query' => 'SELECT * FROM foreign_history',
                ]
            );

        $this->get(
            route(
                'query-history.show',
                $foreignActivity
            )
        )
            ->assertNotFound();
    }

    public function test_sql_query_index_only_contains_active_current_team_connections(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $teamAActive =
            $this->createConnectionForTeam(
                $this->team,
                'Team A Active SQL',
                'team_a_active_sql',
                true
            );

        $teamAInactive =
            $this->createConnectionForTeam(
                $this->team,
                'Team A Inactive SQL',
                'team_a_inactive_sql',
                false
            );

        $teamBActive =
            $this->createConnectionForTeam(
                $otherTeam,
                'Team B Active SQL',
                'team_b_active_sql',
                true
            );

        $response =
            $this->get(
                route('sql-query.index')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'connections',
                function ($connections) use (
                    $teamAActive,
                    $teamAInactive,
                    $teamBActive
                ): bool {
                    return $connections->contains(
                        'id',
                        $teamAActive->id
                    )
                        && ! $connections->contains(
                            'id',
                            $teamAInactive->id
                        )
                        && ! $connections->contains(
                            'id',
                            $teamBActive->id
                        );
                }
            );
    }

    public function test_sql_query_execute_rejects_cross_team_connection_before_connector(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $foreignConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign SQL Database',
                'foreign_sql_database'
            );

        $connector =
            $this->mock(
                DatabaseConnectorService::class
            );

        $connector->shouldNotReceive(
            'withConnection'
        );

        $this->post(
            route('sql-query.execute'),
            [
                'database_connection_id' => $foreignConnection->id,

                'query' => 'SELECT 1',
            ]
        )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'database_activities',
            [
                'team_id' => $this->team->id,

                'database_connection_id' => $foreignConnection->id,
            ]
        );
    }

    private function actingAsTeamAdmin(): User
    {
        $user =
            User::factory()->create();

        $this->team->members()->attach(
            $user->id,
            [
                'role' => 'admin',
            ]
        );

        $user->forceFill([
            'current_team_id' => $this->team->id,
        ])->save();

        $user->unsetRelation(
            'currentTeam'
        );

        $user->refresh();

        $this->actingAs($user);

        return $user;
    }

    private function createConnectionForTeam(
        Team $team,
        string $name,
        string $database,
        bool $isActive = true
    ): DatabaseConnection {
        $connection =
            new DatabaseConnection([
                'name' => $name,
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => $database,
                'username' => 'test_user',
                'password' => null,
                'schema' => null,
                'is_active' => $isActive,
            ]);

        $connection->team_id =
            $team->id;

        $connection->save();

        return $connection;
    }

    private function createActivityForTeam(
        Team $team,
        DatabaseConnection $connection,
        array $attributes = []
    ): DatabaseActivity {
        $activity =
            new DatabaseActivity(
                array_merge([
                    'database_connection_id' => $connection->id,

                    'database_name' => $connection->database,

                    'schema_name' => null,

                    'table_name' => null,

                    'username' => $connection->username,

                    'client_ip' => '127.0.0.1',

                    'action' => 'QUERY',

                    'query' => 'SELECT 1',

                    'status' => 'success',

                    'error_message' => null,

                    'execution_time_ms' => 10,

                    'executed_at' => now(),
                ], $attributes)
            );

        $activity->team_id =
            $team->id;

        $activity->save();

        return $activity;
    }
}
