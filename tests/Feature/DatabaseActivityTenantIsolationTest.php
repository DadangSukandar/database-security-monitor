<?php

namespace Tests\Feature;

use App\Models\DatabaseActivity;
use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseActivityTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();
    }

    public function test_database_activity_index_only_contains_current_team_data_and_statistics(): void
    {
        $this->actingAsTeamUser();

        $otherTeam =
            Team::factory()->create();

        $teamAConnection =
            $this->createConnectionForTeam(
                $this->team,
                'Team A Activity Database',
                'team_a_activity_database'
            );

        $teamBConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Team B Activity Database',
                'team_b_activity_database'
            );

        $teamASuccess =
            $this->createActivityForTeam(
                $this->team,
                $teamAConnection,
                [
                    'query' => 'SELECT * FROM team_a_success',
                    'status' => 'success',
                    'execution_time_ms' => 100,
                ]
            );

        $teamAFailed =
            $this->createActivityForTeam(
                $this->team,
                $teamAConnection,
                [
                    'query' => 'SELECT * FROM team_a_failed',
                    'status' => 'failed',
                    'execution_time_ms' => 300,
                ]
            );

        $teamBActivity =
            $this->createActivityForTeam(
                $otherTeam,
                $teamBConnection,
                [
                    'query' => 'SELECT * FROM team_b_secret',
                    'status' => 'success',
                    'execution_time_ms' => 999,
                ]
            );

        $response =
            $this->get(
                route(
                    'database-activities.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalActivities',
                2
            )
            ->assertViewHas(
                'successfulActivities',
                1
            )
            ->assertViewHas(
                'failedActivities',
                1
            )
            ->assertViewHas(
                'averageExecutionTime',
                fn ($average): bool => (float) $average === 200.0
            )
            ->assertViewHas(
                'activities',
                function ($activities) use (
                    $teamASuccess,
                    $teamAFailed,
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
                            : collect(
                                $activities
                            );

                    return $items->contains(
                        'id',
                        $teamASuccess->id
                    )
                        && $items->contains(
                            'id',
                            $teamAFailed->id
                        )
                        && ! $items->contains(
                            'id',
                            $teamBActivity->id
                        );
                }
            )
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
            ->assertSee(
                'team_a_success'
            )
            ->assertDontSee(
                'team_b_secret'
            );
    }

    public function test_current_team_can_view_own_database_activity(): void
    {
        $this->actingAsTeamUser();

        $connection =
            $this->createConnectionForTeam(
                $this->team,
                'Owned Activity Database',
                'owned_activity_database'
            );

        $activity =
            $this->createActivityForTeam(
                $this->team,
                $connection,
                [
                    'query' => 'SELECT * FROM owned_activity',
                ]
            );

        $this->get(
            route(
                'database-activities.show',
                $activity
            )
        )
            ->assertOk();
    }

    public function test_cross_team_database_activity_show_returns_not_found(): void
    {
        $this->actingAsTeamUser();

        $otherTeam =
            Team::factory()->create();

        $connection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign Activity Database',
                'foreign_activity_database'
            );

        $activity =
            $this->createActivityForTeam(
                $otherTeam,
                $connection,
                [
                    'query' => 'SELECT * FROM foreign_activity',
                ]
            );

        $this->get(
            route(
                'database-activities.show',
                $activity
            )
        )
            ->assertNotFound();
    }

    public function test_connection_filter_cannot_expose_foreign_team_activity(): void
    {
        $this->actingAsTeamUser();

        $otherTeam =
            Team::factory()->create();

        $foreignConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign Filter Database',
                'foreign_filter_database'
            );

        $foreignActivity =
            $this->createActivityForTeam(
                $otherTeam,
                $foreignConnection,
                [
                    'query' => 'SELECT * FROM foreign_filter_secret',
                ]
            );

        $response =
            $this->get(
                route(
                    'database-activities.index',
                    [
                        'database_connection_id' => $foreignConnection->id,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'activities',
                function ($activities) use (
                    $foreignActivity
                ): bool {
                    $items =
                        method_exists(
                            $activities,
                            'items'
                        )
                            ? collect(
                                $activities->items()
                            )
                            : collect(
                                $activities
                            );

                    return ! $items->contains(
                        'id',
                        $foreignActivity->id
                    );
                }
            )
            ->assertDontSee(
                'foreign_filter_secret'
            );
    }

    private function actingAsTeamUser(): User
    {
        $user =
            User::factory()->create();

        $this->team->members()->attach(
            $user->id,
            [
                'role' => 'member',
            ]
        );

        $user->forceFill([
            'current_team_id' => $this->team->id,
        ])->save();

        $user->unsetRelation(
            'currentTeam'
        );

        $user->refresh();

        $this->actingAs(
            $user
        );

        return $user;
    }

    private function createConnectionForTeam(
        Team $team,
        string $name,
        string $database
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

                'is_active' => true,
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
