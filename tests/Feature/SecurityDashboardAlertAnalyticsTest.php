<?php

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;

it('provides alert lifecycle analytics to the security dashboard', function () {
    $this->actingAs(User::factory()->create());

    $detectedAt = now()->subHours(2);

    $resolvedAlert = SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'CRITICAL',
        'title' => 'Resolved vulnerability',
        'status' => 'RESOLVED',
        'detected_at' => $detectedAt,
        'acknowledged_at' => $detectedAt->copy()->addMinutes(10),
        'resolved_at' => $detectedAt->copy()->addMinutes(60),
    ]);

    SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'HIGH',
        'title' => 'Acknowledged vulnerability',
        'status' => 'ACKNOWLEDGED',
        'detected_at' => $detectedAt,
        'acknowledged_at' => $detectedAt->copy()->addMinutes(20),
    ]);

    SecurityAlertHistory::query()->create([
        'security_alert_id' => $resolvedAlert->id,
        'action' => 'RESOLVE',
        'old_status' => 'ACKNOWLEDGED',
        'new_status' => 'RESOLVED',
        'notes' => 'Verified remediation.',
    ]);

    $this->get(route('security-dashboard'))
        ->assertOk()
        ->assertViewHas('acknowledgedAlerts', 2)
        ->assertViewHas('acknowledgementRate', 100.0)
        ->assertViewHas('resolutionRate', 50.0)
        ->assertViewHas('averageAcknowledgementMinutes', 15.0)
        ->assertViewHas('averageResolutionMinutes', 60.0)
        ->assertViewHas('recentAlertActivity', fn ($activity): bool => $activity->count() === 1);
});

it('returns empty lifecycle metrics when no alerts exist', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('security-dashboard'))
        ->assertOk()
        ->assertViewHas('acknowledgedAlerts', 0)
        ->assertViewHas('acknowledgementRate', 0.0)
        ->assertViewHas('resolutionRate', 0.0)
        ->assertViewHas('averageAcknowledgementMinutes', null)
        ->assertViewHas('averageResolutionMinutes', null);
});
