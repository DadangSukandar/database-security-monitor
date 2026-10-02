<?php

namespace Tests\Feature;

use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityFindingQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

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
    }

    public function test_finding_index_query_count_does_not_grow_with_rows(): void
    {
        $this->createFinding();

        $singleFindingQueryCount =
            count(
                $this->captureSelectQueries()
            );

        foreach (range(2, 30) as $number) {
            $this->createFinding([
                'title' => 'Query Budget Finding '.$number,
            ]);
        }

        $manyFindingQueryCount =
            count(
                $this->captureSelectQueries()
            );

        $this->assertLessThanOrEqual(
            $singleFindingQueryCount + 1,
            $manyFindingQueryCount,
            sprintf(
                'Security finding index query count grew with row count. '.
                '1 finding = %d SELECT queries, '.
                '30 findings = %d SELECT queries.',
                $singleFindingQueryCount,
                $manyFindingQueryCount
            )
        );
    }

    public function test_finding_index_uses_at_most_five_finding_select_queries(): void
    {
        foreach (range(1, 30) as $number) {
            $this->createFinding([
                'title' => 'Finding Budget '.$number,
            ]);
        }

        $queries =
            $this->captureSelectQueries();

        $findingQueries =
            collect($queries)
                ->filter(
                    function (array $query): bool {
                        return str_contains(
                            strtolower($query['query']),
                            'security_findings'
                        );
                    }
                );

        $this->assertLessThanOrEqual(
            5,
            $findingQueries->count(),
            'Security finding index melakukan terlalu banyak '.
            "SELECT ke security_findings:\n".
            $findingQueries
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
                route('security-findings.index')
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

    private function createFinding(
        array $attributes = []
    ): SecurityFinding {
        $sequence =
            $this->sequence++;

        $finding =
            new SecurityFinding(
                array_merge(
                    [
                        'database_name' => 'query_budget_database',

                        'finding_type' => 'QUERY_BUDGET_'.$sequence,

                        'category' => 'ACCESS_CONTROL',

                        'severity' => match ($sequence % 4) {
                            0 => 'CRITICAL',
                            1 => 'HIGH',
                            2 => 'MEDIUM',
                            default => 'LOW',
                        },

                        'title' => 'Query Budget Finding '.$sequence,

                        'description' => 'Query budget test finding.',

                        'status' => 'OPEN',

                        'detected_at' => now()->subMinutes($sequence),
                    ],
                    $attributes
                )
            );

        $finding->team_id =
            $this->team->id;

        $finding->save();

        return $finding;
    }
}
