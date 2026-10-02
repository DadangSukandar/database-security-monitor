<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\Team;
use App\Models\User;
use App\Models\VulnerabilityAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityReportQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    private DatabaseConnection $connection;

    private int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();

        $this->user =
            User::factory()->create();

        $this->team
            ->members()
            ->attach(
                $this->user->id,
                [
                    'role' => 'admin',
                ]
            );

        $this->user->forceFill([
            'current_team_id' => $this->team->id,
        ])->save();

        $this->user->unsetRelation(
            'currentTeam'
        );

        $this->user->refresh();

        $this->actingAs(
            $this->user
        );

        $this->connection =
            $this->createConnection();
    }

    public function test_report_index_query_count_does_not_grow_with_assessment_rows(): void
    {
        $this->createAssessment();

        $singleAssessmentQueryCount =
            count(
                $this->captureSelectQueries()
            );

        foreach (range(2, 15) as $number) {
            $this->createAssessment([
                'database_name' => 'Query Budget Database '.$number,
            ]);
        }

        $manyAssessmentQueryCount =
            count(
                $this->captureSelectQueries()
            );

        $this->assertLessThanOrEqual(
            $singleAssessmentQueryCount + 1,
            $manyAssessmentQueryCount,
            sprintf(
                'Security report index query count grew with row count. '.
                '1 assessment = %d SELECT queries, '.
                '15 assessments = %d SELECT queries.',
                $singleAssessmentQueryCount,
                $manyAssessmentQueryCount
            )
        );
    }

    public function test_report_index_uses_at_most_six_assessment_select_queries(): void
    {
        foreach (range(1, 15) as $number) {
            $this->createAssessment([
                'database_name' => 'Assessment Budget '.$number,
            ]);
        }

        $queries =
            $this->captureSelectQueries();

        $assessmentQueries =
            collect($queries)
                ->filter(
                    function (array $query): bool {
                        return str_contains(
                            strtolower($query['query']),
                            'vulnerability_assessments'
                        );
                    }
                );

        $this->assertLessThanOrEqual(
            6,
            $assessmentQueries->count(),
            'Security report index melakukan terlalu banyak '.
            "SELECT ke vulnerability_assessments:\n".
            $assessmentQueries
                ->pluck('query')
                ->implode("\n")
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function captureSelectQueries(): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response =
            $this->get(
                route('security-reports.index')
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
                ->values()
                ->all();

        DB::disableQueryLog();
        DB::flushQueryLog();

        return $queries;
    }

    private function createConnection(): DatabaseConnection
    {
        $connection =
            new DatabaseConnection([
                'name' => 'Security Report Query Budget',

                'driver' => 'mysql',

                'host' => '127.0.0.1',

                'port' => 3306,

                'database' => 'security_report_budget',

                'username' => 'test_user',

                'password' => 'secret',

                'is_active' => true,
            ]);

        $connection->team_id =
            $this->team->id;

        $connection->save();

        return $connection;
    }

    private function createAssessment(
        array $attributes = []
    ): VulnerabilityAssessment {
        $sequence =
            $this->sequence++;

        $assessment =
            new VulnerabilityAssessment(
                array_merge(
                    [
                        'database_connection_id' => $this->connection->id,

                        'database_name' => 'Query Budget Database '.$sequence,

                        'score' => 50 + ($sequence % 50),

                        'critical_count' => $sequence % 3,

                        'high_count' => $sequence % 4,

                        'medium_count' => $sequence % 5,

                        'low_count' => $sequence % 6,

                        'status' => 'COMPLETED',

                        'scanned_at' => now()->subMinutes(
                            100 - $sequence
                        ),
                    ],
                    $attributes
                )
            );

        $assessment->save();

        return $assessment;
    }
}
