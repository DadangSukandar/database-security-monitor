<?php

namespace Tests\Feature;

use App\Models\SecurityPolicy;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityPolicyQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    private int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();

        $this->user = User::factory()->create();

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

    public function test_policy_index_query_count_does_not_grow_with_policy_rows(): void
    {
        $this->createPolicy();

        $singlePolicyQueryCount =
            count(
                $this->captureSelectQueriesForPolicyIndex()
            );

        foreach (range(2, 15) as $number) {
            $this->createPolicy([
                'name' => 'Query Budget Policy '.$number,
            ]);
        }

        $manyPolicyQueryCount =
            count(
                $this->captureSelectQueriesForPolicyIndex()
            );

        $this->assertLessThanOrEqual(
            $singlePolicyQueryCount + 1,
            $manyPolicyQueryCount,
            sprintf(
                'Security policy index query count grew with row count. '.
                '1 policy = %d SELECT queries, '.
                '15 policies = %d SELECT queries.',
                $singlePolicyQueryCount,
                $manyPolicyQueryCount
            )
        );
    }

    public function test_policy_index_uses_at_most_three_security_policy_select_queries(): void
    {
        foreach (range(1, 15) as $number) {
            $this->createPolicy([
                'name' => 'Aggregate Budget Policy '.$number,
            ]);
        }

        $queries =
            $this->captureSelectQueriesForPolicyIndex();

        $policyQueries =
            collect($queries)
                ->filter(
                    function (array $query): bool {
                        $sql = strtolower(
                            $query['query']
                        );

                        return str_contains(
                            $sql,
                            'security_policies'
                        );
                    }
                );

        $this->assertLessThanOrEqual(
            3,
            $policyQueries->count(),
            'Security policy index masih melakukan terlalu banyak '.
            "SELECT query ke security_policies:\n".
            $policyQueries
                ->pluck('query')
                ->implode("\n")
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function captureSelectQueriesForPolicyIndex(): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(
            route('security-policies.index')
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

    private function createPolicy(
        array $attributes = []
    ): SecurityPolicy {
        $sequence = $this->sequence++;

        $policy =
            new SecurityPolicy(
                array_merge(
                    [
                        'name' => 'Query Budget Policy '.$sequence,

                        'code' => sprintf(
                            'QUERY_BUDGET_%04d',
                            $sequence
                        ),

                        'rule_type' => 'ACCESS_CONTROL',

                        'severity' => $sequence % 4 === 0
                                ? 'CRITICAL'
                                : 'HIGH',

                        'conditions' => [
                        'source' => 'query-budget-test',
                        ],

                        'priority' => $sequence,

                        'is_active' => $sequence % 2 !== 0,
                    ],
                    $attributes
                )
            );

        $policy->team_id =
            $this->team->id;

        $policy->save();

        return $policy;
    }
}
