<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use App\Services\SecurityAuditScanner;
use App\Services\SecurityFindingLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_security_audit_index_only_contains_current_team_data(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $teamAConnection = $this->createConnectionForTeam(
            $this->team,
            'Team A Database',
            'team_a_database'
        );

        $teamBConnection = $this->createConnectionForTeam(
            $otherTeam,
            'Team B Database',
            'team_b_database'
        );

        $teamACritical = $this->createFindingForTeam(
            $this->team,
            [
                'database_connection_id' => $teamAConnection->id,
                'database_name' => $teamAConnection->database,
                'finding_type' => 'TEAM_A_CRITICAL',
                'severity' => 'CRITICAL',
                'title' => 'Team A Critical Audit Finding',
                'status' => 'OPEN',
            ]
        );

        $teamAHigh = $this->createFindingForTeam(
            $this->team,
            [
                'database_connection_id' => $teamAConnection->id,
                'database_name' => $teamAConnection->database,
                'finding_type' => 'TEAM_A_HIGH',
                'severity' => 'HIGH',
                'title' => 'Team A High Audit Finding',
                'status' => 'OPEN',
            ]
        );

        $teamAResolved = $this->createFindingForTeam(
            $this->team,
            [
                'database_connection_id' => $teamAConnection->id,
                'database_name' => $teamAConnection->database,
                'finding_type' => 'TEAM_A_RESOLVED',
                'severity' => 'MEDIUM',
                'title' => 'Team A Resolved Audit Finding',
                'status' => 'RESOLVED',
            ]
        );

        $teamBSecret = $this->createFindingForTeam(
            $otherTeam,
            [
                'database_connection_id' => $teamBConnection->id,
                'database_name' => $teamBConnection->database,
                'finding_type' => 'TEAM_B_SECRET',
                'severity' => 'CRITICAL',
                'title' => 'Team B Secret Audit Finding',
                'status' => 'OPEN',
            ]
        );

        $response = $this->get(
            route('security-audit.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'total',
                3
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
                'open',
                2
            )
            ->assertViewHas(
                'resolved',
                1
            )
            ->assertViewHas(
                'score',
                55
            )
            ->assertViewHas(
                'findings',
                function ($findings) use (
                    $teamACritical,
                    $teamAHigh,
                    $teamAResolved,
                    $teamBSecret
                ): bool {
                    $items = method_exists(
                        $findings,
                        'items'
                    )
                        ? collect(
                            $findings->items()
                        )
                        : collect($findings);

                    return $items->contains(
                        'id',
                        $teamACritical->id
                    )
                        && $items->contains(
                            'id',
                            $teamAHigh->id
                        )
                        && $items->contains(
                            'id',
                            $teamAResolved->id
                        )
                        && ! $items->contains(
                            'id',
                            $teamBSecret->id
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
                'Team A Critical Audit Finding'
            )
            ->assertDontSee(
                'Team B Secret Audit Finding'
            );
    }

    public function test_current_team_can_view_own_security_audit_finding(): void
    {
        $this->actingAsTeamAdmin();

        $finding = $this->createFindingForTeam(
            $this->team,
            [
                'title' => 'Owned Audit Finding',
            ]
        );

        $this->get(
            route(
                'security-audit.show',
                $finding
            )
        )
            ->assertOk()
            ->assertSee(
                'Owned Audit Finding'
            );
    }

    public function test_cross_team_security_audit_scan_returns_not_found_before_scanner(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $foreignConnection =
            $this->createConnectionForTeam(
                $otherTeam,
                'Foreign Database',
                'foreign_database'
            );

        $scanner = $this->mock(
            SecurityAuditScanner::class
        );

        $scanner->shouldNotReceive(
            'scan'
        );

        $this->post(
            route('security-audit.scan'),
            [
                'database_connection_id' => $foreignConnection->id,
            ]
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_audit_show_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
                'title' => 'Foreign Audit Finding',
            ]
        );

        $this->get(
            route(
                'security-audit.show',
                $finding
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_audit_resolve_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
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
                'security-audit.resolve',
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

    public function test_cross_team_security_audit_ignore_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
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
                'security-audit.ignore',
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

    public function test_cross_team_security_audit_reopen_returns_not_found_before_lifecycle(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $finding = $this->createFindingForTeam(
            $otherTeam,
            [
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
                'security-audit.reopen',
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

    private function actingAsTeamAdmin(): User
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
        $connection = new DatabaseConnection([
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

        $connection->team_id = $team->id;

        $connection->save();

        return $connection;
    }

    private function createFindingForTeam(
        Team $team,
        array $attributes = []
    ): SecurityFinding {
        $finding = new SecurityFinding(
            array_merge([
                'database_connection_id' => null,
                'database_name' => 'audit_test_database',
                'finding_type' => 'AUDIT_TEST_FINDING',
                'category' => 'ACCESS_CONTROL',
                'severity' => 'HIGH',
                'title' => 'Security Audit Finding',
                'description' => 'Security audit tenant isolation finding.',
                'object_type' => 'DATABASE',
                'object_name' => 'audit_test_object',
                'username' => 'audit_test_user',
                'recommendation' => 'Review security configuration.',
                'status' => 'OPEN',
                'detected_at' => now(),
            ], $attributes)
        );

        $finding->team_id = $team->id;

        $finding->save();

        return $finding;
    }
}
