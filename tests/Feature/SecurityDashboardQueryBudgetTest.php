<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityDashboardQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_dashboard_does_not_issue_repeated_alert_count_queries(): void
    {
        $team =
            Team::factory()->create();

        $user =
            User::factory()->create();

        $team->members()->attach(
            $user->id,
            [
                'role' => 'admin',
            ]
        );

        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->actingAs(
            $user->refresh()
        );

        $this->createAlert(
            $team,
            'OPEN',
            'CRITICAL'
        );

        $this->createAlert(
            $team,
            'OPEN',
            'HIGH'
        );

        $this->createAlert(
            $team,
            'OPEN',
            'MEDIUM'
        );

        $this->createAlert(
            $team,
            'ACKNOWLEDGED',
            'HIGH',
            acknowledgedAt: now()
        );

        $this->createAlert(
            $team,
            'INVESTIGATING',
            'HIGH'
        );

        $this->createAlert(
            $team,
            'RESOLVED',
            'LOW',
            resolvedAt: now()
        );

        $incidentOperationalQueries = [];

        DB::listen(
            function ($query) use (&$incidentOperationalQueries): void {
                $sql =
                    strtolower(
                        $query->sql
                    );

                if (
                    str_contains(
                        $sql,
                        'breached_incident_sla'
                    )
                    && str_contains(
                        $sql,
                        'due_soon_incident_sla'
                    )
                    && str_contains(
                        $sql,
                        'p1_incidents'
                    )
                    && str_contains(
                        $sql,
                        'p2_incidents'
                    )
                ) {
                    $incidentOperationalQueries[] =
                        $query->sql;
                }
            }
        );

        $statisticQueries = [];

        DB::listen(
            function ($query) use (&$statisticQueries): void {
                $sql =
                    strtolower(
                        $query->sql
                    );

                if (
                    str_contains(
                        $sql,
                        'security_alerts'
                    )
                    && str_contains(
                        $sql,
                        'total_alerts'
                    )
                    && str_contains(
                        $sql,
                        'total_open_alerts'
                    )
                    && str_contains(
                        $sql,
                        'critical_alerts'
                    )
                    && str_contains(
                        $sql,
                        'high_alerts'
                    )
                    && str_contains(
                        $sql,
                        'resolved_alerts'
                    )
                    && str_contains(
                        $sql,
                        'acknowledged_alerts'
                    )
                ) {
                    $statisticQueries[] =
                        $query->sql;
                }
            }
        );

        $slaQueries = [];

        DB::listen(
            function ($query) use (&$slaQueries): void {
                $sql =
                    strtolower(
                        $query->sql
                    );

                if (
                    str_contains(
                        $sql,
                        'breached_sla_alerts'
                    )
                    && str_contains(
                        $sql,
                        'due_soon_sla_alerts'
                    )
                ) {
                    $slaQueries[] =
                        $query->sql;
                }
            }
        );

        $response =
            $this->get(
                route(
                    'security-dashboard'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalAlerts',
                6
            )
            ->assertViewHas(
                'totalOpenAlerts',
                3
            )
            ->assertViewHas(
                'criticalAlerts',
                1
            )
            ->assertViewHas(
                'highAlerts',
                1
            )
            ->assertViewHas(
                'resolvedAlerts',
                1
            )
            ->assertViewHas(
                'acknowledgedAlerts',
                1
            );

        $this->assertCount(
            1,
            $incidentOperationalQueries,
            'Security Dashboard harus menghitung incident SLA '.
            'dan triage metrics dalam satu SQL statement.'.
            PHP_EOL.
            implode(
                PHP_EOL,
                $incidentOperationalQueries
            )
        );

        $this->assertCount(
            1,
            $slaQueries,
            'Security Dashboard harus menghitung BREACHED '.
            'dan DUE_SOON dalam satu SQL statement.'.
            PHP_EOL.
            implode(
                PHP_EOL,
                $slaQueries
            )
        );

        $this->assertCount(
            1,
            $statisticQueries,
            'Statistik utama Security Dashboard harus dihitung '.
            'dengan tepat satu conditional aggregate query.'.
            PHP_EOL.
            implode(
                PHP_EOL,
                $statisticQueries
            )
        );
    }

    private function createAlert(
        Team $team,
        string $status,
        string $severity,
        $acknowledgedAt = null,
        $resolvedAt = null
    ): SecurityAlert {
        $alert =
            new SecurityAlert([
                'alert_type' => 'VULNERABILITY',

                'severity' => $severity,

                'title' => $status.' '.$severity.' Alert',

                'description' => 'Security dashboard query budget test.',

                'status' => $status,

                'detected_at' => now()->subMinutes(10),

                'acknowledged_at' => $acknowledgedAt,

                'resolved_at' => $resolvedAt,

                'occurrence_count' => 1,

                'first_seen_at' => now()->subMinutes(10),

                'last_seen_at' => now(),
            ]);

        $alert->team_id =
            $team->id;

        $alert->save();

        return $alert;
    }
}
