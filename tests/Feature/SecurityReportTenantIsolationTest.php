<?php

namespace Tests\Feature;

use App\Http\Controllers\VulnerabilityAssessmentController;
use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Models\VulnerabilityAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityReportTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_report_index_only_contains_current_team_assessments_and_statistics(): void
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

        $teamAOld = $this->createAssessment(
            $teamAConnection,
            [
                'score' => 80,
                'critical_count' => 1,
                'high_count' => 2,
                'medium_count' => 3,
                'low_count' => 4,
                'scanned_at' => now()->subDays(2),
            ]
        );

        $teamANew = $this->createAssessment(
            $teamAConnection,
            [
                'score' => 100,
                'critical_count' => 0,
                'high_count' => 1,
                'medium_count' => 0,
                'low_count' => 1,
                'scanned_at' => now()->subDay(),
            ]
        );

        $teamBAssessment = $this->createAssessment(
            $teamBConnection,
            [
                'score' => 1,
                'critical_count' => 99,
                'high_count' => 99,
                'medium_count' => 99,
                'low_count' => 99,
                'scanned_at' => now(),
            ]
        );

        $response = $this->get(
            route('security-reports.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalAssessments',
                2
            )
            ->assertViewHas(
                'critical',
                1
            )
            ->assertViewHas(
                'high',
                3
            )
            ->assertViewHas(
                'medium',
                3
            )
            ->assertViewHas(
                'low',
                5
            )
            ->assertViewHas(
                'averageScore',
                90.0
            )
            ->assertViewHas(
                'latestAssessment',
                fn ($assessment): bool => $assessment?->id === $teamANew->id
            )
            ->assertViewHas(
                'bestAssessment',
                fn ($assessment): bool => $assessment?->id === $teamANew->id
            )
            ->assertViewHas(
                'worstAssessment',
                fn ($assessment): bool => $assessment?->id === $teamAOld->id
            )
            ->assertViewHas(
                'assessments',
                function ($assessments) use (
                    $teamAOld,
                    $teamANew,
                    $teamBAssessment
                ): bool {
                    $items = method_exists(
                        $assessments,
                        'items'
                    )
                        ? collect($assessments->items())
                        : collect($assessments);

                    return $items->contains('id', $teamAOld->id)
                        && $items->contains('id', $teamANew->id)
                        && ! $items->contains(
                            'id',
                            $teamBAssessment->id
                        );
                }
            )
            ->assertSee('Team A Database')
            ->assertDontSee('Team B Database');
    }

    public function test_current_team_can_access_own_security_report(): void
    {
        $this->actingAsTeamAdmin();

        $connection = $this->createConnectionForTeam(
            $this->team,
            'Owned Database',
            'owned_database'
        );

        $assessment = $this->createAssessment(
            $connection
        );

        $this->get(
            route(
                'security-reports.show',
                $assessment
            )
        )
            ->assertOk();
    }

    public function test_cross_team_security_report_detail_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $connection = $this->createConnectionForTeam(
            $otherTeam,
            'Foreign Database',
            'foreign_database'
        );

        $assessment = $this->createAssessment(
            $connection
        );

        $this->get(
            route(
                'security-reports.show',
                $assessment
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_report_print_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $connection = $this->createConnectionForTeam(
            $otherTeam,
            'Foreign Print Database',
            'foreign_print_database'
        );

        $assessment = $this->createAssessment(
            $connection
        );

        $this->get(
            route(
                'security-reports.print',
                $assessment
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_report_comparison_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $connection = $this->createConnectionForTeam(
            $otherTeam,
            'Foreign Comparison Database',
            'foreign_comparison_database'
        );

        $assessment = $this->createAssessment(
            $connection
        );

        $this->get(
            route(
                'security-reports.comparison',
                $assessment
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_report_rerun_returns_not_found_before_scan(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam = Team::factory()->create();

        $connection = $this->createConnectionForTeam(
            $otherTeam,
            'Foreign Rerun Database',
            'foreign_rerun_database'
        );

        $assessment = $this->createAssessment(
            $connection
        );

        $scanner = $this->mock(
            VulnerabilityAssessmentController::class
        );

        $scanner->shouldNotReceive('scan');

        $this->post(
            route(
                'security-reports.rerun',
                $assessment
            )
        )
            ->assertNotFound();
    }

    private function actingAsTeamAdmin(
        array $attributes = []
    ): User {
        $user = User::factory()->create(
            $attributes
        );

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

        $assessment = new VulnerabilityAssessment(
            array_merge([
                'database_connection_id' => $connection->id,
                'database_name' => $connection->database,
                'score' => 100,
                'critical_count' => 0,
                'high_count' => 0,
                'medium_count' => 0,
                'low_count' => 0,
                'status' => 'COMPLETED',
            ], $attributes)
        );

        $assessment->scanned_at = $scannedAt;

        $assessment->save();

        return $assessment;
    }
}
