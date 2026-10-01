<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();
    }

    public function test_dashboard_only_uses_current_team_data(): void
    {
        $this->actingAsTeamUser();

        $otherTeam =
            Team::factory()->create();

        $teamAConnection =
            $this->createConnection(
                $this->team,
                'Team A Dashboard DB',
                'team_a_dashboard'
            );

        $teamBConnection =
            $this->createConnection(
                $otherTeam,
                'Team B Secret DB',
                'team_b_dashboard'
            );

        $teamACritical =
            $this->createFinding(
                $this->team,
                $teamAConnection,
                [
                    'severity' => 'CRITICAL',

                    'title' => 'Team A Critical Finding',
                ]
            );

        $teamALow =
            $this->createFinding(
                $this->team,
                $teamAConnection,
                [
                    'severity' => 'LOW',

                    'title' => 'Team A Low Finding',
                ]
            );

        $teamBFinding =
            $this->createFinding(
                $otherTeam,
                $teamBConnection,
                [
                    'severity' => 'CRITICAL',

                    'title' => 'Team B Secret Finding',
                ]
            );

        $response =
            $this->get(
                route('dashboard')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalConnections',
                1
            )
            ->assertViewHas(
                'activeConnections',
                1
            )
            ->assertViewHas(
                'criticalFindings',
                1
            )
            ->assertViewHas(
                'highFindings',
                0
            )
            ->assertViewHas(
                'mediumFindings',
                0
            )
            ->assertViewHas(
                'lowFindings',
                1
            )
            ->assertViewHas(
                'totalFindings',
                2
            )
            ->assertViewHas(
                'securityScore',
                function ($score): bool {
                    return $score['score'] === 73
                        && $score['critical'] === 1
                        && $score['low'] === 1
                        && $score['total'] === 2;
                }
            )
            ->assertViewHas(
                'databaseConnections',
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
                'recentSecurityFindings',
                function ($findings) use (
                    $teamACritical,
                    $teamALow,
                    $teamBFinding
                ): bool {
                    return $findings->contains(
                        'id',
                        $teamACritical->id
                    )
                        && $findings->contains(
                            'id',
                            $teamALow->id
                        )
                        && ! $findings->contains(
                            'id',
                            $teamBFinding->id
                        );
                }
            )
            ->assertSee(
                'Team A Critical Finding'
            )
            ->assertDontSee(
                'Team B Secret Finding'
            );
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(
            route('dashboard')
        )
            ->assertRedirect();
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

    private function createConnection(
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

                'is_active' => true,
            ]);

        $connection->team_id =
            $team->id;

        $connection->save();

        return $connection;
    }

    private function createFinding(
        Team $team,
        DatabaseConnection $connection,
        array $attributes = []
    ): SecurityFinding {
        $finding =
            new SecurityFinding(
                array_merge([
                    'database_connection_id' => $connection->id,

                    'database_name' => $connection->database,

                    'finding_type' => 'DASHBOARD_TEST',

                    'category' => 'ACCESS_CONTROL',

                    'severity' => 'HIGH',

                    'title' => 'Dashboard Finding',

                    'description' => 'Dashboard tenant isolation test.',

                    'object_type' => 'DATABASE',

                    'object_name' => $connection->database,

                    'username' => 'test_user',

                    'recommendation' => 'Review access.',

                    'status' => 'OPEN',

                    'detected_at' => now(),
                ], $attributes)
            );

        $finding->team_id =
            $team->id;

        $finding->save();

        return $finding;
    }
}
