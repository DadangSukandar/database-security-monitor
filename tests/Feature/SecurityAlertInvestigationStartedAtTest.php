<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Services\SecurityAlertLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAlertInvestigationStartedAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_investigation_records_started_at_timestamp(): void
    {
        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Investigation timestamp test',
            'description' => 'Runtime lifecycle regression test.',
            'status' => 'OPEN',
            'detected_at' => now()->subHour(),
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
            'sla_started_at' => now()->subHour(),
            'occurrence_count' => 1,
        ]);

        $acknowledgedAt = now()
            ->subMinutes(10)
            ->startOfSecond();

        $investigationStartedAt = now()
            ->startOfSecond();

        $lifecycle = app(
            SecurityAlertLifecycleService::class
        );

        $lifecycle->acknowledge(
            $alert,
            null,
            $acknowledgedAt
        );

        $lifecycle->investigate(
            $alert->fresh(),
            'Runtime investigation test.',
            null,
            $investigationStartedAt
        );

        $investigating = $alert->fresh();

        $this->assertSame(
            'INVESTIGATING',
            $investigating->status
        );

        $this->assertNotNull(
            $investigating->acknowledged_at
        );

        $this->assertNotNull(
            $investigating->investigation_started_at
        );

        $this->assertTrue(
            $investigating
                ->acknowledged_at
                ->equalTo($acknowledgedAt)
        );

        $this->assertTrue(
            $investigating
                ->investigation_started_at
                ->equalTo($investigationStartedAt)
        );

        $this->assertDatabaseHas(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'START_INVESTIGATION',
                'old_status' => 'ACKNOWLEDGED',
                'new_status' => 'INVESTIGATING',
                'notes' => 'Runtime investigation test.',
            ]
        );
    }

    public function test_reopen_clears_previous_investigation_timestamp(): void
    {
        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Investigation reopen test',
            'description' => 'Runtime lifecycle regression test.',
            'status' => 'OPEN',
            'detected_at' => now()->subHour(),
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subHour(),
            'sla_started_at' => now()->subHour(),
            'occurrence_count' => 1,
        ]);

        $lifecycle = app(
            SecurityAlertLifecycleService::class
        );

        $investigationStartedAt = now()
            ->subMinutes(5)
            ->startOfSecond();

        $resolvedAt = now()
            ->subMinutes(2)
            ->startOfSecond();

        $reopenedAt = now()
            ->startOfSecond();

        $lifecycle->investigate(
            $alert,
            'Testing investigation.',
            null,
            $investigationStartedAt
        );

        $lifecycle->resolve(
            $alert->fresh(),
            'Runtime audit resolution.',
            null,
            $resolvedAt
        );

        $resolved = $alert->fresh();

        $this->assertNotNull(
            $resolved->investigation_started_at
        );

        $this->assertNotNull(
            $resolved->resolved_at
        );

        $lifecycle->reopen(
            $resolved,
            null,
            $reopenedAt
        );

        $reopened = $alert->fresh();

        $this->assertSame(
            'OPEN',
            $reopened->status
        );

        $this->assertNull(
            $reopened->acknowledged_at
        );

        $this->assertNull(
            $reopened->investigation_started_at
        );

        $this->assertNull(
            $reopened->resolved_at
        );

        $this->assertNull(
            $reopened->resolution_note
        );

        $this->assertTrue(
            $reopened
                ->sla_started_at
                ->equalTo($reopenedAt)
        );

        $this->assertDatabaseHas(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'REOPEN',
                'old_status' => 'RESOLVED',
                'new_status' => 'OPEN',
            ]
        );
    }
}
