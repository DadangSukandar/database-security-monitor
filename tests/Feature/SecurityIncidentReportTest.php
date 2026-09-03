<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SecurityIncidentReportTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 1;

    protected function setUp(): void
    {
        parent::setUp();

        self::$sequence = 1;
        Carbon::setTestNow(Carbon::parse('2026-09-03 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_access_incident_reporting(): void
    {
        $this->get(route('security-incidents.reports.index'))
            ->assertRedirect();
    }

    public function test_report_defaults_to_last_thirty_days_and_exposes_breakdowns(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createIncident([
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'opened_at' => now()->subDays(2),
            'acknowledged_at' => now()->subDays(2)->addMinutes(10),
            'resolved_at' => now()->subDays(2)->addMinutes(60),
            'closed_at' => now()->subDays(2)->addMinutes(90),
        ]);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now()->subDay(),
        ]);

        $this->createIncident([
            'severity' => 'LOW',
            'status' => 'OPEN',
            'opened_at' => now()->subDays(40),
        ]);

        $this->get(route('security-incidents.reports.index'))
            ->assertOk()
            ->assertSee('Incident Reporting & Audit', false)
            ->assertViewHas('startDate', fn (Carbon $date): bool => $date->toDateString() === '2026-08-05')
            ->assertViewHas('endDate', fn (Carbon $date): bool => $date->toDateString() === '2026-09-03')
            ->assertViewHas('report', function (array $report): bool {
                return $report['summary']['total'] === 2
                    && $report['summary']['active'] === 1
                    && $report['summary']['closed'] === 1
                    && $report['severity_breakdown']['CRITICAL'] === 1
                    && $report['severity_breakdown']['HIGH'] === 1
                    && $report['status_breakdown']['OPEN'] === 1
                    && $report['status_breakdown']['CLOSED'] === 1
                    && $report['priority_breakdown']['NONE'] === 1;
            });
    }

    public function test_report_calculates_acknowledgement_sla_and_resolution_metrics(): void
    {
        $this->actingAs(User::factory()->create());

        $opened = now()->subDays(3);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'CLOSED',
            'opened_at' => $opened,
            'acknowledged_at' => $opened->copy()->addMinutes(30),
            'resolved_at' => $opened->copy()->addMinutes(120),
            'closed_at' => $opened->copy()->addMinutes(150),
        ]);

        $opened = now()->subDays(2);

        $this->createIncident([
            'severity' => 'HIGH',
            'status' => 'RESOLVED',
            'opened_at' => $opened,
            'acknowledged_at' => $opened->copy()->addMinutes(90),
            'resolved_at' => $opened->copy()->addMinutes(240),
        ]);

        $this->get(route('security-incidents.reports.index'))
            ->assertOk()
            ->assertViewHas('report', function (array $report): bool {
                return $report['sla']['acknowledged'] === 2
                    && $report['sla']['met'] === 1
                    && $report['sla']['breached'] === 1
                    && $report['sla']['met_rate'] === 50.0
                    && $report['sla']['average_acknowledgement_minutes'] === 60.0
                    && $report['sla']['average_resolution_minutes'] === 180.0;
            });
    }

    public function test_trend_counts_resolution_event_even_when_incident_opened_before_range(): void
    {
        $this->actingAs(User::factory()->create());

        $incident = $this->createIncident([
            'status' => 'RESOLVED',
            'opened_at' => Carbon::parse('2026-08-01 08:00:00'),
            'acknowledged_at' => Carbon::parse('2026-08-01 08:30:00'),
            'resolved_at' => Carbon::parse('2026-09-02 12:00:00'),
        ]);

        SecurityIncidentHistory::query()->create([
            'security_incident_id' => $incident->id,
            'action' => 'RESOLVE',
            'old_status' => 'CONTAINED',
            'new_status' => 'RESOLVED',
            'created_at' => Carbon::parse('2026-09-02 12:00:00'),
            'updated_at' => Carbon::parse('2026-09-02 12:00:00'),
        ]);

        $this->get(route('security-incidents.reports.index', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
        ]))
            ->assertOk()
            ->assertViewHas('report', function (array $report): bool {
                $septemberSecond = collect($report['trends'])
                    ->firstWhere('date', '2026-09-02');

                return $report['summary']['total'] === 0
                    && $septemberSecond['resolved'] === 1
                    && $report['audit_summary']['lifecycle'] === 1;
            });
    }

    public function test_report_rejects_invalid_or_excessive_date_ranges(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('security-incidents.reports.index', [
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-01',
        ]))->assertSessionHasErrors('start_date');

        $this->get(route('security-incidents.reports.index', [
            'start_date' => '2025-01-01',
            'end_date' => '2026-09-03',
        ]))->assertSessionHasErrors('start_date');

        $this->get(route('security-incidents.reports.index', [
            'start_date' => 'not-a-date',
            'end_date' => '2026-09-03',
        ]))->assertSessionHasErrors('start_date');
    }

    public function test_audit_report_displays_actor_activity_and_notes(): void
    {
        $actor = User::factory()->create(['name' => 'SOC Reporter']);
        $this->actingAs($actor);

        $incident = $this->createIncident([
            'opened_at' => now()->subDay(),
        ]);

        SecurityIncidentHistory::query()->create([
            'security_incident_id' => $incident->id,
            'action' => 'INVESTIGATION_NOTE',
            'old_status' => 'OPEN',
            'new_status' => 'OPEN',
            'notes' => 'Evidence reviewed for reporting.',
            'user_id' => $actor->id,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this->get(route('security-incidents.reports.index'))
            ->assertOk()
            ->assertSee('SOC Reporter')
            ->assertSee('Investigation Note')
            ->assertSee('Evidence reviewed for reporting.')
            ->assertViewHas('auditActivities', fn ($activities): bool => $activities->total() === 1);
    }

    private function createIncident(array $attributes = []): SecurityIncident
    {
        $sequence = self::$sequence++;

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Report source alert '.$sequence,
            'description' => 'Source alert for incident reporting.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(array_merge([
            'incident_number' => sprintf('INC-REPORT-%04d', $sequence),
            'security_alert_id' => $alert->id,
            'title' => 'Reporting incident '.$sequence,
            'description' => 'Reporting test incident.',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now(),
        ], $attributes));
    }
}
