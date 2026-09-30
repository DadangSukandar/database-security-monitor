<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use App\Models\VulnerabilityAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRiskTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_security_risk_only_uses_current_team_data(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $teamAConnection =
            $this->createConnectionForTeam(
                $this->team,
                'Team A Database',
                'team_a_database'
            );

        $teamBConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Team B Database',
                'team_b_database'
            );

        $teamACritical =
            $this->createFindingForTeam(
                $this->team,
                [
                    'database_name' => 'team_a_database',
                    'category' => 'ACCESS_CONTROL',
                    'severity' => 'CRITICAL',
                    'title' => 'Team A Critical Risk',
                    'status' => 'OPEN',
                ]
            );

        $teamAHigh =
            $this->createFindingForTeam(
                $this->team,
                [
                    'database_name' => 'team_a_database',
                    'category' => 'PRIVILEGE',
                    'severity' => 'HIGH',
                    'title' => 'Team A High Risk',
                    'status' => 'OPEN',
                ]
            );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_database',
                'category' => 'CONFIGURATION',
                'severity' => 'MEDIUM',
                'title' => 'Team A Resolved Risk',
                'status' => 'RESOLVED',
                'resolved_at' => now(),
            ]
        );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_database',
                'category' => 'AUTHENTICATION',
                'severity' => 'LOW',
                'title' => 'Team A Ignored Risk',
                'status' => 'IGNORED',
            ]
        );

        $teamBSecret =
            $this->createFindingForTeam(
                $otherTeam,
                [
                    'database_name' => 'team_b_secret_database',
                    'category' => 'TEAM_B_ONLY',
                    'severity' => 'CRITICAL',
                    'title' => 'Team B Secret Risk',
                    'status' => 'OPEN',
                ]
            );

        $teamAAssessment =
            $this->createAssessment(
                $teamAConnection,
                [
                    'score' => 80,
                    'scanned_at' => now()->subDay(),
                ]
            );

        $teamBAssessment =
            $this->createAssessment(
                $teamBConnection,
                [
                    'score' => 1,
                    'scanned_at' => now(),
                ]
            );

        $response = $this->get(
            route('security-risk.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalFindings',
                4
            )
            ->assertViewHas(
                'openFindings',
                2
            )
            ->assertViewHas(
                'resolvedFindings',
                1
            )
            ->assertViewHas(
                'ignoredFindings',
                1
            )
            ->assertViewHas(
                'openCritical',
                1
            )
            ->assertViewHas(
                'openHigh',
                1
            )
            ->assertViewHas(
                'openMedium',
                0
            )
            ->assertViewHas(
                'openLow',
                0
            )
            ->assertViewHas(
                'riskPoints',
                60
            )
            ->assertViewHas(
                'securityScore',
                40
            )
            ->assertViewHas(
                'latestAssessment',
                fn ($assessment): bool => $assessment?->id
                    === $teamAAssessment->id
            )
            ->assertViewHas(
                'topRisks',
                function ($risks) use (
                    $teamACritical,
                    $teamAHigh,
                    $teamBSecret
                ): bool {
                    return $risks->contains(
                        'id',
                        $teamACritical->id
                    )
                        && $risks->contains(
                            'id',
                            $teamAHigh->id
                        )
                        && ! $risks->contains(
                            'id',
                            $teamBSecret->id
                        );
                }
            )
            ->assertViewHas(
                'databaseRisk',
                fn ($rows): bool => $rows->pluck(
                    'database_name'
                )->contains(
                    'team_a_database'
                )
                    && ! $rows->pluck(
                        'database_name'
                    )->contains(
                        'team_b_secret_database'
                    )
            )
            ->assertViewHas(
                'categoryDistribution',
                fn ($rows): bool => $rows->pluck(
                    'category'
                )->contains(
                    'ACCESS_CONTROL'
                )
                    && ! $rows->pluck(
                        'category'
                    )->contains(
                        'TEAM_B_ONLY'
                    )
            )
            ->assertViewHas(
                'recentFindings',
                fn ($findings): bool => ! $findings->contains(
                    'id',
                    $teamBSecret->id
                )
            )
            ->assertViewHas(
                'riskTrend',
                fn ($trend): bool => $trend->contains(
                    'id',
                    $teamAAssessment->id
                )
                    && ! $trend->contains(
                        'id',
                        $teamBAssessment->id
                    )
            )
            ->assertSee(
                'Team A Critical Risk'
            )
            ->assertDontSee(
                'Team B Secret Risk'
            );
    }

    private function actingAsTeamUser(): User
    {
        $user = User::factory()->create();

        $this->team->members()->attach(
            $user->id,
            [
                'role' => 'admin',
            ]
        );

        $user->forceFill([
            'current_team_id' => $this->team->id,
        ])->save();

        $user->refresh();

        $this->actingAs($user);

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
                'is_active' => true,
            ]);

        $connection->team_id = $team->id;

        $connection->save();

        return $connection;
    }

    private function createFindingForTeam(
        Team $team,
        array $attributes = []
    ): SecurityFinding {
        $finding =
            new SecurityFinding(
                array_merge([
                    'database_connection_id' => null,
                    'database_name' => 'risk_test_database',
                    'finding_type' => 'RISK_TEST_FINDING',
                    'category' => 'ACCESS_CONTROL',
                    'severity' => 'HIGH',
                    'title' => 'Risk Test Finding',
                    'status' => 'OPEN',
                    'detected_at' => now(),
                ], $attributes)
            );

        $finding->team_id =
            $team->id;

        $finding->save();

        return $finding;
    }

    private function createAssessment(
        DatabaseConnection $connection,
        array $attributes = []
    ): VulnerabilityAssessment {
        $scannedAt =
            $attributes['scanned_at']
            ?? now();

        unset(
            $attributes['scanned_at']
        );

        $assessment =
            VulnerabilityAssessment::query()
                ->create(
                    array_merge([
                        'database_connection_id' => $connection->id,
                        'database_name' => $connection->database,
                        'score' => 100,
                        'critical_count' => 0,
                        'high_count' => 0,
                        'medium_count' => 0,
                        'low_count' => 0,
                        'status' => 'COMPLETED',
                        'scanned_at' => $scannedAt,
                    ], $attributes)
                );

        return $assessment;
    }
}
