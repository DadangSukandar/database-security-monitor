<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityIncidentQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    private static int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        self::$sequence = 1;

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

        $this->user->unsetRelation('currentTeam');
        $this->user->refresh();

        $this->actingAs($this->user);
    }

    public function test_incident_index_query_count_does_not_grow_with_incident_rows(): void
    {
        $this->createIncident([
            'title' => 'Budget incident 1',
        ]);

        $singleIncidentQueryCount =
            $this->countSelectQueriesForIncidentIndex();

        foreach (range(2, 20) as $number) {
            $this->createIncident([
                'title' => 'Budget incident '.$number,
            ]);
        }

        $manyIncidentQueryCount =
            $this->countSelectQueriesForIncidentIndex();

        $this->assertLessThanOrEqual(
            $singleIncidentQueryCount + 1,
            $manyIncidentQueryCount,
            sprintf(
                'Security incident index query count grew with row count. '.
                '1 incident = %d SELECT queries, 20 incidents = %d SELECT queries.',
                $singleIncidentQueryCount,
                $manyIncidentQueryCount
            )
        );
    }

    private function countSelectQueriesForIncidentIndex(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(
            route('security-incidents.index')
        );

        $response->assertOk();

        $queries = collect(
            DB::getQueryLog()
        )->filter(
            fn (array $query): bool => str_starts_with(
                strtolower(
                    ltrim($query['query'])
                ),
                'select'
            )
        );

        DB::disableQueryLog();
        DB::flushQueryLog();

        return $queries->count();
    }

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $sequence = self::$sequence++;

        $alert = new SecurityAlert([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Query budget source alert '.$sequence,
            'description' => 'Source alert for incident query budget testing.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $alert->team_id = $this->team->id;

        $alert->save();

        $incident = new SecurityIncident(
            array_merge([
                'incident_number' => sprintf(
                    'INC-BUDGET-%04d',
                    $sequence
                ),
                'security_alert_id' => $alert->id,
                'title' => 'Query budget incident '.$sequence,
                'description' => 'Incident used for query budget regression testing.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'opened_at' => now()->subMinutes(
                    120 + $sequence
                ),
                'assigned_to_user_id' => $this->user->id,
                'created_by_user_id' => $this->user->id,
            ], $attributes)
        );

        $incident->team_id = $this->team->id;

        $incident->save();

        return $incident;
    }
}
