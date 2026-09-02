<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function createAlert(
        array $attributes = []
    ): SecurityAlert {
        return SecurityAlert::query()->create(array_merge([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Incident metrics source alert',
            'description' => 'Source alert for incident metrics test.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        static $sequence = 0;

        $sequence++;

        $alert = $this->createAlert();

        return SecurityIncident::query()->create(array_merge([
            'incident_number' => sprintf(
                'INC-%s-%04d',
                now()->format('Ymd'),
                $sequence
            ),
            'security_alert_id' => $alert->id,
            'title' => 'Incident metrics test',
            'description' => 'Incident used for operational metrics.',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now(),
        ], $attributes));
    }

    public function test_incident_index_exposes_operational_metrics(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'INVESTIGATING',
        ]);

        $this->createIncident([
            'severity' => 'MEDIUM',
            'status' => 'ACKNOWLEDGED',
        ]);

        $this->createIncident([
            'severity' => 'LOW',
            'status' => 'CONTAINED',
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentMetrics',
                function (array $metrics): bool {
                    return $metrics === [
                        'active' => 4,
                        'open' => 1,
                        'investigating' => 1,
                        'critical_high' => 2,
                        'unassigned' => 4,
                    ];
                }
            );
    }

    public function test_closed_incidents_are_excluded_from_active_metrics(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentMetrics',
                function (array $metrics): bool {
                    return $metrics['active'] === 0
                        && $metrics['critical_high'] === 0
                        && $metrics['unassigned'] === 0;
                }
            );
    }

    public function test_critical_and_high_metric_only_counts_active_incidents(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'INVESTIGATING',
        ]);

        $this->createIncident([
            'severity' => 'MEDIUM',
            'status' => 'OPEN',
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentMetrics',
                fn (array $metrics): bool => $metrics['critical_high'] === 2
            );
    }

    public function test_unassigned_metric_only_counts_active_unassigned_incidents(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $this->createIncident([
            'status' => 'OPEN',
            'assigned_to_user_id' => null,
        ]);

        $this->createIncident([
            'status' => 'INVESTIGATING',
            'assigned_to_user_id' => null,
        ]);

        $this->createIncident([
            'status' => 'OPEN',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $this->createIncident([
            'status' => 'CLOSED',
            'assigned_to_user_id' => null,
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentMetrics',
                fn (array $metrics): bool => $metrics['unassigned'] === 2
            );
    }

    public function test_metrics_do_not_follow_incident_list_filters(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'incident_number' => 'INC-FILTER-0001',
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
        ]);

        $this->createIncident([
            'incident_number' => 'INC-FILTER-0002',
            'severity' => 'HIGH',
            'status' => 'INVESTIGATING',
        ]);

        $this->createIncident([
            'incident_number' => 'INC-FILTER-0003',
            'severity' => 'LOW',
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'status' => 'CLOSED',
            ]));

        $response
            ->assertOk()
            ->assertSee('INC-FILTER-0003')
            ->assertDontSee('INC-FILTER-0001')
            ->assertDontSee('INC-FILTER-0002')
            ->assertViewHas(
                'incidentMetrics',
                function (array $metrics): bool {
                    return $metrics['active'] === 2
                        && $metrics['open'] === 1
                        && $metrics['investigating'] === 1
                        && $metrics['critical_high'] === 2
                        && $metrics['unassigned'] === 2;
                }
            );
    }

    public function test_guest_cannot_access_incident_metrics_page(): void
    {
        $response = $this->get(
            route('security-incidents.index')
        );

        $response->assertRedirect();
    }

    public function test_oldest_active_metric_uses_longest_running_active_incident(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $now = now();

        $this->createIncident([
            'status' => 'OPEN',
            'opened_at' => $now->copy()->subHours(2),
        ]);

        $this->createIncident([
            'status' => 'INVESTIGATING',
            'opened_at' => $now->copy()->subHours(6),
        ]);

        $this->createIncident([
            'status' => 'CLOSED',
            'opened_at' => $now->copy()->subDays(3),
            'closed_at' => $now->copy()->subDays(2),
        ]);

        $response = $this->get(
            route('security-incidents.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentAgingMetrics',
                fn (array $metrics): bool => $metrics['oldest_active'] === '6h'
            );
    }
}
