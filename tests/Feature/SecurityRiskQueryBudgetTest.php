<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use App\Models\VulnerabilityAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityRiskQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    public function test_security_risk_query_count_does_not_grow_with_rows(): void
    {
        $this->actingAsTeamUser();

        $connection =
            $this->createConnectionForTeam(
                $this->team,
                'Query Budget Database',
                'query_budget_database'
            );

        $this->createFindingForTeam(
            $this->team,
            [
                'database_name' => 'query_budget_database',

                'category' => 'ACCESS_CONTROL',

                'severity' => 'CRITICAL',

                'title' => 'Query Budget Finding 1',

                'status' => 'OPEN',
            ]
        );

        $this->createAssessment(
            $connection,
            [
                'score' => 80,
                'scanned_at' => now(),
            ]
        );

        $singleRowQueries =
            $this->captureDomainSelectQueries();

        foreach (range(2, 30) as $number) {
            $this->createFindingForTeam(
                $this->team,
                [
                    'database_name' => 'query_budget_database',

                    'category' => 'ACCESS_CONTROL',

                    'severity' => match ($number % 4) {
                        0 => 'CRITICAL',
                        1 => 'HIGH',
                        2 => 'MEDIUM',
                        default => 'LOW',
                    },

                    'title' => 'Query Budget Finding '.$number,

                    'status' => 'OPEN',
                ]
            );

            $this->createAssessment(
                $connection,
                [
                    'score' => 50 + ($number % 40),

                    'scanned_at' => now()->subMinutes($number),
                ]
            );
        }

        $manyRowQueries =
            $this->captureDomainSelectQueries();

        $this->assertSame(
            count($singleRowQueries),
            count($manyRowQueries),
            sprintf(
                'Security risk query count grew with row count. '.
                '1-row dataset = %d domain SELECTs, '.
                '30-row dataset = %d domain SELECTs.',
                count($singleRowQueries),
                count($manyRowQueries)
            )
        );
    }

    public function test_security_risk_index_stays_within_domain_query_budget(): void
    {
        $this->actingAsTeamUser();

        $connection =
            $this->createConnectionForTeam(
                $this->team,
                'Risk Budget Database',
                'risk_budget_database'
            );

        foreach (range(1, 20) as $number) {
            $this->createFindingForTeam(
                $this->team,
                [
                    'database_name' => 'risk_budget_database',

                    'category' => 'ACCESS_CONTROL',

                    'severity' => match ($number % 4) {
                        0 => 'CRITICAL',
                        1 => 'HIGH',
                        2 => 'MEDIUM',
                        default => 'LOW',
                    },

                    'title' => 'Risk Budget Finding '.$number,

                    'status' => 'OPEN',
                ]
            );
        }

        foreach (range(1, 10) as $number) {
            $this->createAssessment(
                $connection,
                [
                    'score' => 60 + $number,

                    'scanned_at' => now()->subMinutes($number),
                ]
            );
        }

        $queries =
            collect(
                $this->captureDomainSelectQueries()
            );

        $findingQueries =
            $queries->filter(
                fn (array $query): bool => $this->selectsFromTable(
                    $query,
                    'security_findings'
                )
            );

        $assessmentQueries =
            $queries->filter(
                fn (array $query): bool => $this->selectsFromTable(
                    $query,
                    'vulnerability_assessments'
                )
            );

        $connectionQueries =
            $queries->filter(
                fn (array $query): bool => $this->selectsFromTable(
                    $query,
                    'database_connections'
                )
            );

        $this->assertLessThanOrEqual(
            6,
            $findingQueries->count(),
            'SecurityRiskController melebihi budget '.
            "SELECT security_findings:\n".
            $findingQueries
                ->pluck('query')
                ->implode("\n")
        );

        $this->assertLessThanOrEqual(
            3,
            $assessmentQueries->count(),
            'SecurityRiskController melebihi budget '.
            "SELECT vulnerability_assessments:\n".
            $assessmentQueries
                ->pluck('query')
                ->implode("\n")
        );

        $this->assertLessThanOrEqual(
            1,
            $connectionQueries->count(),
            'SecurityRiskController melebihi budget '.
            "SELECT database_connections:\n".
            $connectionQueries
                ->pluck('query')
                ->implode("\n")
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function captureDomainSelectQueries(): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response =
            $this->get(
                route('security-risk.index')
            );

        $response->assertOk();

        $queries =
            collect(DB::getQueryLog())
                ->filter(
                    fn (array $query): bool => str_starts_with(
                        strtolower(
                            ltrim($query['query'])
                        ),
                        'select'
                    )
                )
                ->filter(
                    function (array $query): bool {
                        $sql =
                            strtolower(
                                $query['query']
                            );

                        return
                            str_contains(
                                $sql,
                                'security_findings'
                            ) ||
                            str_contains(
                                $sql,
                                'vulnerability_assessments'
                            ) ||
                            str_contains(
                                $sql,
                                'database_connections'
                            );
                    }
                )
                ->values()
                ->all();

        DB::disableQueryLog();
        DB::flushQueryLog();

        return $queries;
    }

    private function selectsFromTable(
        array $query,
        string $table
    ): bool {
        $sql =
            preg_replace(
                '/\s+/',
                ' ',
                strtolower(
                    trim($query['query'])
                )
            );

        if (! is_string($sql)) {
            return false;
        }

        $matched =
            preg_match(
                '/\bfrom\s+["`\[]?([a-z0-9_]+)["`\]]?/i',
                $sql,
                $matches
            );

        if ($matched !== 1) {
            return false;
        }

        return strtolower($matches[1]) ===
            strtolower($table);
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
