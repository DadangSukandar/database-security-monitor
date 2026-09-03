<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentSlaParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_sla_scope_matches_model_sla_status(): void
    {
        $now = now()->startOfSecond();

        $incidents = collect([
            $this->incident(
                'CRITICAL',
                'OPEN',
                $now->copy()->subMinutes(20)
            ),

            $this->incident(
                'CRITICAL',
                'OPEN',
                $now->copy()->subMinutes(13)
            ),

            $this->incident(
                'CRITICAL',
                'OPEN',
                $now->copy()->subMinutes(2)
            ),

            $this->incident(
                'HIGH',
                'OPEN',
                $now->copy()->subMinutes(70)
            ),

            $this->incident(
                'HIGH',
                'OPEN',
                $now->copy()->subMinutes(50)
            ),

            $this->incident(
                'HIGH',
                'OPEN',
                $now->copy()->subMinutes(10)
            ),

            $this->incident(
                'MEDIUM',
                'OPEN',
                $now->copy()->subMinutes(250)
            ),

            $this->incident(
                'MEDIUM',
                'OPEN',
                $now->copy()->subMinutes(190)
            ),

            $this->incident(
                'MEDIUM',
                'OPEN',
                $now->copy()->subMinutes(20)
            ),

            $this->incident(
                'LOW',
                'OPEN',
                $now->copy()->subMinutes(1500)
            ),

            $this->incident(
                'LOW',
                'OPEN',
                $now->copy()->subMinutes(1100)
            ),

            $this->incident(
                'LOW',
                'OPEN',
                $now->copy()->subMinutes(60)
            ),

            $this->incident(
                'HIGH',
                'ACKNOWLEDGED',
                $now->copy()->subMinutes(120),
                $now->copy()->subMinutes(70)
            ),

            $this->incident(
                'HIGH',
                'ACKNOWLEDGED',
                $now->copy()->subMinutes(120),
                $now->copy()->subMinutes(50)
            ),

            $this->incident(
                'LOW',
                'CLOSED',
                $now->copy()->subMinutes(30)
            ),

            $this->incident(
                'LOW',
                'OPEN',
                null
            ),
        ]);

        foreach (
            [
                'BREACHED',
                'DUE_SOON',
                'ON_TRACK',
                'MET',
                'UNKNOWN',
            ] as $status
        ) {
            $expected = $incidents
                ->filter(
                    fn (SecurityIncident $incident): bool => $incident->responseSlaStatus($now) === $status
                )
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $actual = SecurityIncident::query()
                ->whereResponseSlaStatus($status)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                $expected,
                $actual,
                "SQL SLA scope mismatch for {$status}."
            );
        }
    }

    private function incident(
        string $severity,
        string $status,
        $openedAt,
        $acknowledgedAt = null
    ): SecurityIncident {
        static $sequence = 0;

        $sequence++;

        $alert = SecurityAlert::query()->create([
            'alert_code' => sprintf(
                'ALT-SLA-PARITY-%04d',
                $sequence
            ),
            'title' => 'SLA parity alert '.$sequence,
            'description' => 'Alert fixture for SLA parity test.',
            'severity' => $severity,
            'status' => 'OPEN',
            'detected_at' => now(),
        ]);

        return SecurityIncident::query()->create([
            'incident_number' => sprintf(
                'INC-SLA-PARITY-%04d',
                $sequence
            ),
            'security_alert_id' => $alert->id,
            'title' => 'SLA parity incident '.$sequence,
            'severity' => $severity,
            'status' => $status,
            'opened_at' => $openedAt,
            'acknowledged_at' => $acknowledgedAt,
        ]);
    }
}
