<?php

namespace Tests\Feature;

use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use App\Services\SecurityFindingLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityFindingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_security_finding_index_only_contains_current_team_data_and_statistics(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_primary',
                'finding_type' => 'TEAM_A_CRITICAL',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'CRITICAL',
                'title' => 'Team A Critical Finding',
                'status' => 'OPEN',
            ]
        );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_primary',
                'finding_type' => 'TEAM_A_HIGH',
                'category' => 'PRIVILEGE',
                'severity' => 'HIGH',
                'title' => 'Team A High Finding',
                'status' => 'OPEN',
            ]
        );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_archive',
                'finding_type' => 'TEAM_A_RESOLVED',
                'category' => 'CONFIGURATION',
                'severity' => 'MEDIUM',
                'title' => 'Team A Resolved Finding',
                'status' => 'RESOLVED',
            ]
        );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'team_a_archive',
                'finding_type' => 'TEAM_A_IGNORED',
                'category' => 'AUTHENTICATION',
                'severity' => 'LOW',
                'title' => 'Team A Ignored Finding',
                'status' => 'IGNORED',
            ]
        );

        $this->createFindingForTeam(
            $otherTeam,
            [
                'database_name' => 'team_b_hidden_database',
                'finding_type' => 'TEAM_B_SECRET',
                'category' => 'TEAM_B_ONLY',
                'severity' => 'CRITICAL',
                'title' => 'Team B Secret Finding',
                'status' => 'OPEN',
            ]
        );

        $response = $this->get(
            route('security-findings.index')
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
                'critical',
                1
            )
            ->assertViewHas(
                'high',
                1
            )
            ->assertViewHas(
                'medium',
                0
            )
            ->assertViewHas(
                'low',
                0
            )
            ->assertViewHas(
                'databases',
                function ($databases): bool {
                    return $databases->contains(
                        'team_a_primary'
                    )
                        && $databases->contains(
                            'team_a_archive'
                        )
                        && ! $databases->contains(
                            'team_b_hidden_database'
                        );
                }
            )
            ->assertViewHas(
                'categories',
                function ($categories): bool {
                    return $categories->contains(
                        'ACCESS_CONTROL'
                    )
                        && $categories->contains(
                            'PRIVILEGE'
                        )
                        && $categories->contains(
                            'CONFIGURATION'
                        )
                        && $categories->contains(
                            'AUTHENTICATION'
                        )
                        && ! $categories->contains(
                            'TEAM_B_ONLY'
                        );
                }
            )
            ->assertSee(
                'Team A Critical Finding'
            )
            ->assertSee(
                'Team A High Finding'
            )
            ->assertDontSee(
                'Team B Secret Finding'
            )
            ->assertDontSee(
                'team_b_hidden_database'
            );
    }

    public function test_current_team_can_view_own_security_finding(): void
    {
        $this->actingAsTeamUser();

        $finding = $this->createFindingForTeam(
            $this->team,
            [
                'finding_type' => 'OWN_FINDING',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'HIGH',
                'title' => 'Owned Security Finding',
                'status' => 'OPEN',
            ]
        );

        $this->get(
            route(
                'security-findings.show',
                $finding
            )
        )
            ->assertOk()
            ->assertSee(
                'Owned Security Finding'
            );
    }

    public function test_cross_team_security_finding_show_returns_not_found(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
                'finding_type' => 'FOREIGN_SHOW',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'HIGH',
                'title' => 'Foreign Show Finding',
                'status' => 'OPEN',
            ]
        );

        $this->get(
            route(
                'security-findings.show',
                $finding
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_finding_resolve_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
                'finding_type' => 'FOREIGN_RESOLVE',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'HIGH',
                'title' => 'Foreign Resolve Finding',
                'status' => 'OPEN',
            ]
        );

        $lifecycle = $this->mock(
            SecurityFindingLifecycleService::class
        );

        $lifecycle->shouldNotReceive(
            'resolve'
        );

        $this->post(
            route(
                'security-findings.resolve',
                $finding
            )
        )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'security_findings',
            [
                'id' => $finding->id,
                'status' => 'OPEN',
            ]
        );
    }

    public function test_cross_team_security_finding_ignore_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
                'finding_type' => 'FOREIGN_IGNORE',
                'category' => 'PRIVILEGE',
                'severity' => 'MEDIUM',
                'title' => 'Foreign Ignore Finding',
                'status' => 'OPEN',
            ]
        );

        $lifecycle = $this->mock(
            SecurityFindingLifecycleService::class
        );

        $lifecycle->shouldNotReceive(
            'ignore'
        );

        $this->post(
            route(
                'security-findings.ignore',
                $finding
            )
        )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'security_findings',
            [
                'id' => $finding->id,
                'status' => 'OPEN',
            ]
        );
    }

    public function test_cross_team_security_finding_reopen_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
                'finding_type' => 'FOREIGN_REOPEN',
                'category' => 'CONFIGURATION',
                'severity' => 'LOW',
                'title' => 'Foreign Reopen Finding',
                'status' => 'RESOLVED',
            ]
        );

        $lifecycle = $this->mock(
            SecurityFindingLifecycleService::class
        );

        $lifecycle->shouldNotReceive(
            'reopen'
        );

        $this->post(
            route(
                'security-findings.reopen',
                $finding
            )
        )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'security_findings',
            [
                'id' => $finding->id,
                'status' => 'RESOLVED',
            ]
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
            'current_team_id' =>
                $this->team->id,
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

    private function createFindingForTeam(
        Team $team,
        array $attributes = []
    ): SecurityFinding {
        $finding = new SecurityFinding(
            array_merge([
                'database_connection_id' => null,
                'database_name' => 'test_database',
                'finding_type' => 'TEST_FINDING',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'HIGH',
                'title' => 'Tenant Security Finding',
                'description' =>
                    'Tenant isolation security finding.',
                'object_type' => 'DATABASE',
                'object_name' => 'test_object',
                'username' => 'test_user',
                'recommendation' =>
                    'Review security configuration.',
                'status' => 'OPEN',
                'detected_at' => now(),
            ], $attributes)
        );

        $finding->team_id = $team->id;

        $finding->save();

        return $finding;
    }
}