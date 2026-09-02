<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentAgingTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Incident aging source alert',
            'description' => 'Source alert for incident aging test.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' => 'INC-AGING-0001',
                'security_alert_id' => $alert->id,
                'title' => 'Incident aging test',
                'description' => 'Incident used for aging tests.',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'opened_at' => now(),
            ], $attributes)
        );
    }

    public function test_active_incident_age_uses_current_time(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'status' => 'OPEN',
            'opened_at' => $now
                ->copy()
                ->subHours(2)
                ->subMinutes(15),
        ]);

        $this->assertSame(
            135,
            $incident->ageMinutes($now)
        );

        $this->assertSame(
            '2h 15m',
            $incident->ageLabel($now)
        );
    }

    public function test_closed_incident_age_stops_at_closed_at(): void
    {
        $openedAt = Carbon::parse(
            '2026-09-01 08:00:00'
        );

        $closedAt = Carbon::parse(
            '2026-09-01 14:30:00'
        );

        $muchLater = Carbon::parse(
            '2026-09-02 18:00:00'
        );

        $incident = $this->createIncident([
            'status' => 'CLOSED',
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
        ]);

        $this->assertSame(
            390,
            $incident->ageMinutes($muchLater)
        );

        $this->assertSame(
            '6h 30m',
            $incident->ageLabel($muchLater)
        );
    }

    public function test_age_label_formats_minutes(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'opened_at' => $now
                ->copy()
                ->subMinutes(42),
        ]);

        $this->assertSame(
            '42m',
            $incident->ageLabel($now)
        );
    }

    public function test_age_label_formats_exact_hours(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'opened_at' => $now
                ->copy()
                ->subHours(4),
        ]);

        $this->assertSame(
            '4h',
            $incident->ageLabel($now)
        );
    }

    public function test_age_label_formats_days_and_hours(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'opened_at' => $now
                ->copy()
                ->subDays(2)
                ->subHours(6),
        ]);

        $this->assertSame(
            '2d 6h',
            $incident->ageLabel($now)
        );
    }

    public function test_age_label_formats_exact_days(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'opened_at' => $now
                ->copy()
                ->subDays(3),
        ]);

        $this->assertSame(
            '3d',
            $incident->ageLabel($now)
        );
    }

    public function test_missing_opened_at_returns_unknown_age(): void
    {
        $incident = $this->createIncident();

        $incident->opened_at = null;

        $this->assertNull(
            $incident->ageMinutes()
        );

        $this->assertSame(
            'Unknown',
            $incident->ageLabel()
        );
    }

    public function test_resolved_incident_continues_aging_until_closed(): void
    {
        $now = Carbon::parse(
            '2026-09-02 13:00:00'
        );

        $incident = $this->createIncident([
            'status' => 'RESOLVED',
            'opened_at' => $now
                ->copy()
                ->subHours(5),
            'resolved_at' => $now
                ->copy()
                ->subHours(2),
            'closed_at' => null,
        ]);

        $this->assertSame(
            300,
            $incident->ageMinutes($now)
        );

        $this->assertSame(
            '5h',
            $incident->ageLabel($now)
        );
    }
}
