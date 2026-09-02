<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentSlaTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Incident SLA source alert',
            'description' => 'Source alert for incident SLA test.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        static $sequence = 1;

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' => sprintf(
                    'INC-SLA-%04d',
                    $sequence++
                ),
                'security_alert_id' => $alert->id,
                'title' => 'Incident SLA test',
                'description' => 'Incident used for SLA tests.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'opened_at' => now(),
            ], $attributes)
        );
    }

    public function test_sla_minutes_follow_severity(): void
    {
        $this->assertSame(
            15,
            $this->createIncident([
                'severity' => 'CRITICAL',
            ])->responseSlaMinutes()
        );

        $this->assertSame(
            60,
            $this->createIncident([
                'severity' => 'HIGH',
            ])->responseSlaMinutes()
        );

        $this->assertSame(
            240,
            $this->createIncident([
                'severity' => 'MEDIUM',
            ])->responseSlaMinutes()
        );

        $this->assertSame(
            1440,
            $this->createIncident([
                'severity' => 'LOW',
            ])->responseSlaMinutes()
        );
    }

    public function test_open_incident_is_on_track_before_warning_window(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'severity' => 'HIGH',
            'opened_at' => $openedAt,
        ]);

        $this->assertSame(
            'ON_TRACK',
            $incident->responseSlaStatus(
                $openedAt->copy()->addMinutes(30)
            )
        );
    }

    public function test_open_incident_is_due_soon_in_last_quarter(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'severity' => 'HIGH',
            'opened_at' => $openedAt,
        ]);

        $this->assertSame(
            'DUE_SOON',
            $incident->responseSlaStatus(
                $openedAt->copy()->addMinutes(50)
            )
        );
    }

    public function test_open_incident_is_breached_after_deadline(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'severity' => 'HIGH',
            'opened_at' => $openedAt,
        ]);

        $this->assertSame(
            'BREACHED',
            $incident->responseSlaStatus(
                $openedAt->copy()->addMinutes(61)
            )
        );
    }

    public function test_acknowledgement_before_deadline_meets_sla(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'ACKNOWLEDGED',
            'opened_at' => $openedAt,
            'acknowledged_at' => $openedAt
                ->copy()
                ->addMinutes(45),
        ]);

        $this->assertSame(
            'MET',
            $incident->responseSlaStatus(
                $openedAt->copy()->addHours(4)
            )
        );
    }

    public function test_acknowledgement_after_deadline_is_breached(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'ACKNOWLEDGED',
            'opened_at' => $openedAt,
            'acknowledged_at' => $openedAt
                ->copy()
                ->addMinutes(75),
        ]);

        $this->assertSame(
            'BREACHED',
            $incident->responseSlaStatus()
        );
    }

    public function test_missing_opened_at_returns_unknown(): void
    {
        $incident = $this->createIncident();

        $incident->opened_at = null;

        $this->assertSame(
            'UNKNOWN',
            $incident->responseSlaStatus()
        );
    }

    public function test_closed_unacknowledged_incident_is_breached(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-02 10:00:00'
        );

        $incident = $this->createIncident([
            'status' => 'CLOSED',
            'severity' => 'HIGH',
            'opened_at' => $openedAt,
            'closed_at' => $openedAt
                ->copy()
                ->addMinutes(30),
            'acknowledged_at' => null,
        ]);

        $this->assertSame(
            'BREACHED',
            $incident->responseSlaStatus()
        );
    }
}
