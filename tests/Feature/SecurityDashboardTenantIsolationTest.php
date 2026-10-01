<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityDashboardTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_security_dashboard_only_uses_current_team_assessment_and_findings(): void
    {
        $this->actingAsTeamUser();

        $otherTeam = Team::factory()->create();

        /*
         * =========================================================
         * TEAM A
         * =========================================================
         */

        $teamAConnection = $this->createConnectionForTeam(
            $this->team,
            'Team A Database',
            'team_a_database'
        );

        $teamAAssessment = $this->createAssessment(
            $teamAConnection,
            [
                'score' => 82,
                'critical_count' => 0,
                'high_count' => 1,
                'medium_count' => 0,
                'low_count' => 0,
            ]
        );

        $teamAFinding = $this->createFinding(
            $teamAAssessment,
            [
                'rule_code' => 'TEAM-A-HIGH-001',
                'title' => 'Team A High Finding',
                'severity' => 'HIGH',
                'category' => 'ACCESS_CONTROL',
                'database_name' => 'team_a_database',
            ]
        );

        /*
         * =========================================================
         * TEAM B
         *
         * Dibuat setelah Team A agar ID assessment Team B lebih
         * besar. Kalau query latest assessment masih global,
         * dashboard Team A akan salah memilih assessment Team B.
         * =========================================================
         */

        $teamBConnection = $this->createConnectionForTeam(
            $otherTeam,
            'Team B Database',
            'team_b_database'
        );

        $teamBAssessment = $this->createAssessment(
            $teamBConnection,
            [
                'score' => 10,
                'critical_count' => 1,
                'high_count' => 0,
                'medium_count' => 0,
                'low_count' => 0,
            ]
        );

        $this->createFinding(
            $teamBAssessment,
            [
                'rule_code' => 'TEAM-B-CRITICAL-001',
                'title' => 'Team B Critical Finding',
                'severity' => 'CRITICAL',
                'category' => 'AUTHENTICATION',
                'database_name' => 'team_b_database',
            ]
        );

        $findingStatisticQueries = [];

        DB::listen(
            function ($query) use (&$findingStatisticQueries): void {
                $sql =
                    strtolower(
                        $query->sql
                    );

                if (
                    str_contains(
                        $sql,
                        'vulnerability_findings'
                    )
                    && str_contains(
                        $sql,
                        'total_findings'
                    )
                    && str_contains(
                        $sql,
                        'open_findings'
                    )
                    && str_contains(
                        $sql,
                        'resolved_findings'
                    )
                    && str_contains(
                        $sql,
                        'ignored_findings'
                    )
                    && str_contains(
                        $sql,
                        'critical_findings'
                    )
                    && str_contains(
                        $sql,
                        'high_findings'
                    )
                    && str_contains(
                        $sql,
                        'medium_findings'
                    )
                    && str_contains(
                        $sql,
                        'low_findings'
                    )
                ) {
                    $findingStatisticQueries[] =
                        $query->sql;
                }
            }
        );

        /*
         * =========================================================
         * REQUEST DASHBOARD SEBAGAI TEAM A
         * =========================================================
         */

        $response = $this->get(
            route('security-dashboard')
        );

        $response
            ->assertOk()

            /*
             * Latest assessment harus milik Team A,
             * walaupun assessment Team B dibuat lebih akhir.
             */
            ->assertViewHas(
                'latestAssessment',
                fn ($assessment): bool => $assessment?->id === $teamAAssessment->id
            )

            /*
             * Team A hanya memiliki satu assessment.
             */
            ->assertViewHas(
                'totalAssessments',
                1
            )

            /*
             * History assessment tidak boleh berisi Team B.
             */
            ->assertViewHas(
                'assessmentHistory',
                function ($assessments) use ($teamAAssessment): bool {
                    return $assessments->count() === 1
                        && $assessments->first()?->id
                            === $teamAAssessment->id;
                }
            )

            /*
             * Score harus berasal dari assessment Team A.
             */
            ->assertViewHas(
                'securityScore',
                82
            )

            /*
             * Finding Team B tidak boleh ikut dihitung.
             */
            ->assertViewHas(
                'totalFindings',
                1
            )
            ->assertViewHas(
                'openFindings',
                1
            )
            ->assertViewHas(
                'high',
                1
            )
            ->assertViewHas(
                'critical',
                0
            )

            /*
             * Recent findings hanya boleh finding Team A.
             */
            ->assertViewHas(
                'recentFindings',
                function ($findings) use (
                    $teamAFinding,
                    $teamAAssessment
                ): bool {
                    return $findings->count() === 1
                        && $findings->first()?->id
                            === $teamAFinding->id
                        && $findings->first()
                            ?->vulnerability_assessment_id
                            === $teamAAssessment->id;
                }
            );

        $this->assertCount(
            1,
            $findingStatisticQueries,
            'Security Dashboard harus menghitung finding statistics '.
            'dengan tepat satu conditional aggregate query.'.
            PHP_EOL.
            implode(
                PHP_EOL,
                $findingStatisticQueries
            )
        );
    }

    private function actingAsTeamUser(
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
        return VulnerabilityAssessment::query()
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
                    'scanned_at' => now(),
                ], $attributes)
            );
    }

    private function createFinding(
        VulnerabilityAssessment $assessment,
        array $attributes = []
    ): VulnerabilityFinding {
        return VulnerabilityFinding::query()
            ->create(
                array_merge([
                    'vulnerability_assessment_id' => $assessment->id,
                    'rule_code' => 'TEST-RULE-001',
                    'title' => 'Dashboard tenant finding',
                    'description' => 'Tenant isolation test finding.',
                    'severity' => 'HIGH',
                    'category' => 'ACCESS_CONTROL',
                    'database_name' => $assessment->database_name,
                    'username' => 'test_user',
                    'host' => 'localhost',
                    'evidence' => 'Tenant isolation test evidence.',
                    'recommendation' => 'Review database security.',
                    'resolved' => false,
                    'status' => 'OPEN',
                ], $attributes)
            );
    }
}
