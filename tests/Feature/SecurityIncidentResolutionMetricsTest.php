<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SecurityIncidentResolutionMetricsTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        self::$sequence = 1;
    }

    public function test_incident_index_exposes_resolution_analytics(): void
    {
        Carbon::setTestNow(
            Carbon::parse('2026-09-02 12:00:00')
        );

        $user = User::factory()->create();

        $this->actingAs($user);

        /*
         * HIGH SLA = 60 minutes.
         *
         * Incident #1:
         * ACK = 30 min  -> MET
         * Resolve = 120 min
         */
        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'CLOSED',
            'opened_at' => now()->copy()->subHours(10),
            'acknowledged_at' => now()->copy()
                ->subHours(10)
                ->addMinutes(30),
            'resolved_at' => now()->copy()
                ->subHours(10)
                ->addMinutes(120),
            'closed_at' => now()->copy()
                ->subHours(10)
                ->addMinutes(150),
        ]);

        /*
         * Incident #2:
         * ACK = 90 min -> BREACHED
         * Resolve = 240 min
         */
        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'CLOSED',
            'opened_at' => now()->copy()->subHours(8),
            'acknowledged_at' => now()->copy()
                ->subHours(8)
                ->addMinutes(90),
            'resolved_at' => now()->copy()
                ->subHours(8)
                ->addMinutes(240),
            'closed_at' => now()->copy()
                ->subHours(8)
                ->addMinutes(270),
        ]);

        /*
         * Belum ACK.
         * Tidak boleh masuk historical ACK analytics.
         */
        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now()->copy()->subHours(2),
            'acknowledged_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $response = $this->get(
            route('security-incidents.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentResolutionMetrics',
                function (array $metrics): bool {
                    return
                        $metrics['average_acknowledgement_minutes'] === 60.0
                        && $metrics['average_resolution_minutes'] === 180.0
                        && $metrics['acknowledgement_sla_met'] === 1
                        && $metrics['acknowledgement_sla_breached'] === 1
                        && $metrics['acknowledgement_sla_met_rate'] === 50.0;
                }
            );

        Carbon::setTestNow();
    }

    public function test_unacknowledged_incident_is_excluded_from_acknowledgement_performance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'opened_at' => now()->copy()->subHours(5),
            'acknowledged_at' => null,
        ]);

        $response = $this->get(
            route('security-incidents.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentResolutionMetrics',
                fn (array $metrics): bool => $metrics['average_acknowledgement_minutes'] === null
                    && $metrics['acknowledgement_sla_met'] === 0
                    && $metrics['acknowledgement_sla_breached'] === 0
                    && $metrics['acknowledgement_sla_met_rate'] === null
            );
    }

    public function test_unresolved_incident_is_excluded_from_average_resolution_time(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->createIncident([
            'status' => 'ACKNOWLEDGED',
            'opened_at' => now()->copy()->subHours(2),
            'acknowledged_at' => now()->copy()->subMinutes(90),
            'resolved_at' => null,
        ]);

        $response = $this->get(
            route('security-incidents.index')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'incidentResolutionMetrics',
                fn (array $metrics): bool => $metrics['average_resolution_minutes'] === null
            );
    }

    public function test_guest_cannot_access_resolution_analytics(): void
    {
        $this->get(
            route('security-incidents.index')
        )->assertRedirect();
    }

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $sequence = self::$sequence++;

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Resolution analytics source alert '.$sequence,
            'description' => 'Source alert for incident resolution analytics.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' => sprintf(
                    'INC-20260902-%04d',
                    $sequence
                ),
                'security_alert_id' => $alert->id,
                'title' => 'Resolution analytics incident '.$sequence,
                'description' => 'Test incident.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'opened_at' => now(),
            ], $attributes)
        );
    }
}
