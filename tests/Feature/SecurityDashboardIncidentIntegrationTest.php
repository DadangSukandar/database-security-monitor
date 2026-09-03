<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityDashboardIncidentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $creator = User::factory()->create();

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Security Center incident source',
            'description' => 'Source alert for dashboard integration.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' => 'INC-'.now()->format('Ymd').'-'.
                    str_pad(
                        (string) (SecurityIncident::query()->count() + 1),
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                'security_alert_id' => $alert->id,
                'title' => 'Dashboard integration incident',
                'description' => 'Incident used by Security Center tests.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'created_by_user_id' => $creator->id,
                'opened_at' => now(),
            ], $attributes)
        );
    }

    public function test_security_dashboard_exposes_incident_operational_metrics(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(30),
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'INVESTIGATING',
            'opened_at' => now()->subMinutes(10),
        ]);

        $this->createIncident([
            'severity' => 'LOW',
            'status' => 'CLOSED',
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subHour(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response->assertViewHas(
            'totalIncidents',
            3
        );

        $response->assertViewHas(
            'activeIncidents',
            2
        );

        $response->assertViewHas(
            'closedIncidents',
            1
        );

        $response->assertViewHas(
            'criticalIncidents',
            1
        );

        $response->assertViewHas(
            'highIncidents',
            1
        );

        $response->assertViewHas(
            'unassignedIncidents',
            2
        );
    }

    public function test_security_dashboard_uses_incident_priority_and_sla_semantics(): void
    {
        $user = User::factory()->create();

        /*
         * CRITICAL active incident.
         *
         * Regardless of SLA state, active CRITICAL is P1.
         */
        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(5),
        ]);

        /*
         * HIGH active incident still within SLA.
         *
         * HIGH maps to P2 unless SLA breach elevates it to P1.
         */
        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(5),
        ]);

        /*
         * LOW incident far beyond its response SLA.
         *
         * SLA breach elevates it to P1.
         */
        $this->createIncident([
            'severity' => 'LOW',
            'status' => 'OPEN',
            'opened_at' => now()->subDays(2),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response->assertViewHas(
            'p1Incidents',
            2
        );

        $response->assertViewHas(
            'p2Incidents',
            1
        );

        $response->assertViewHas(
            'breachedIncidentSla',
            1
        );
    }

    public function test_closed_incidents_do_not_enter_active_operational_metrics(): void
    {
        $user = User::factory()->create();

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'opened_at' => now()->subDays(5),
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response->assertViewHas(
            'totalIncidents',
            1
        );

        $response->assertViewHas(
            'closedIncidents',
            1
        );

        $response->assertViewHas(
            'activeIncidents',
            0
        );

        $response->assertViewHas(
            'criticalIncidents',
            0
        );

        $response->assertViewHas(
            'p1Incidents',
            0
        );

        $response->assertViewHas(
            'breachedIncidentSla',
            0
        );

        $response->assertViewHas(
            'dueSoonIncidentSla',
            0
        );
    }

    public function test_security_dashboard_shows_only_five_recent_incidents(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 7) as $index) {
            $this->createIncident([
                'title' => 'Recent incident '.$index,
                'opened_at' => now()->subMinutes(8 - $index),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response->assertViewHas(
            'recentIncidents',
            function ($incidents): bool {
                return $incidents->count() === 5
                    && $incidents->every(
                        fn (SecurityIncident $incident): bool => $incident->relationLoaded('assignedTo')
                            && $incident->relationLoaded('securityAlert')
                    );
            }
        );
    }

    public function test_security_dashboard_contains_incident_navigation(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response
            ->assertSee('Incident Operations')
            ->assertSee('Incident Queue')
            ->assertSee('Reporting &amp; Audit', false)
            ->assertSee(
                route('security-incidents.index'),
                false
            )
            ->assertSee(
                route('security-incidents.reports.index'),
                false
            );
    }

    public function test_recent_incident_links_back_to_source_security_alert(): void
    {
        $user = User::factory()->create();

        $incident = $this->createIncident([
            'title' => 'Cross-linked incident',
            'severity' => 'HIGH',
            'status' => 'OPEN',
        ]);

        $alert = $incident->securityAlert;

        $this->assertNotNull($alert);

        $response = $this
            ->actingAs($user)
            ->get(route('security-dashboard'));

        $response->assertOk();

        $response
            ->assertSee($incident->incident_number)
            ->assertSee('Alert #'.$alert->id)
            ->assertSee(
                route('security-incidents.show', $incident),
                false
            )
            ->assertSee(
                route('security-alerts.show', $alert),
                false
            );
    }
}
