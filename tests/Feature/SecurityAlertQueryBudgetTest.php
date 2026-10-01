<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAlertQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_index_does_not_issue_repeated_alert_statistic_queries(): void
    {
        $team =
            Team::factory()->create();

        $user =
            User::factory()->create();

        $team->members()->attach(
            $user->id,
            [
                'role' => 'member',
            ]
        );

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->actingAs(
            $user->refresh()
        );

        foreach (
            [
                ['OPEN', 'CRITICAL'],
                ['OPEN', 'HIGH'],
                ['OPEN', 'MEDIUM'],
                ['ACKNOWLEDGED', 'HIGH'],
                ['INVESTIGATING', 'HIGH'],
                ['RESOLVED', 'LOW'],
            ] as [$status, $severity]
        ) {
            $alert =
                new SecurityAlert([
                    'alert_type' => 'VULNERABILITY',

                    'severity' => $severity,

                    'title' => $status.' '.$severity.' Alert',

                    'description' => 'Query budget test.',

                    'status' => $status,

                    'detected_at' => now(),

                    'occurrence_count' => 1,

                    'first_seen_at' => now(),

                    'last_seen_at' => now(),
                ]);

            $alert->team_id =
                $team->id;

            $alert->save();
        }

        $queries = [];

        DB::listen(
            function ($query) use (&$queries): void {
                if (
                    str_contains(
                        strtolower($query->sql),
                        'security_alerts'
                    )
                ) {
                    $queries[] =
                        $query->sql;
                }
            }
        );

        $response =
            $this->get(
                route(
                    'security-alerts.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalAlerts',
                6
            )
            ->assertViewHas(
                'openAlerts',
                3
            )
            ->assertViewHas(
                'acknowledgedAlerts',
                1
            )
            ->assertViewHas(
                'investigatingAlerts',
                1
            )
            ->assertViewHas(
                'resolvedAlerts',
                1
            )
            ->assertViewHas(
                'criticalAlerts',
                1
            )
            ->assertViewHas(
                'highAlerts',
                1
            );

        $this->assertLessThanOrEqual(
            5,
            count($queries),
            'Security Alert index melakukan terlalu banyak query ke security_alerts:'.
            PHP_EOL.
            implode(
                PHP_EOL,
                $queries
            )
        );
    }
}
