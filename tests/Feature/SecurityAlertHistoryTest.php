<?php

use App\Models\SecurityAlert;

it('records an alert acknowledgement in history', function () {
    $alert = SecurityAlert::query()->create([
        'severity' => 'HIGH',
        'title' => 'Repeated failed login',
        'status' => 'OPEN',
    ]);

    $this->post(route('security-alerts.acknowledge', $alert))->assertRedirect();

    $this->assertDatabaseHas('security_alerts', [
        'id' => $alert->id,
        'status' => 'ACKNOWLEDGED',
    ]);

    expect($alert->fresh()->acknowledged_at)->not->toBeNull();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'ACKNOWLEDGE',
        'old_status' => 'OPEN',
        'new_status' => 'ACKNOWLEDGED',
    ]);
});

it('does not duplicate history for an idempotent acknowledgement', function () {
    $alert = SecurityAlert::query()->create([
        'severity' => 'MEDIUM',
        'title' => 'Unusual query pattern',
        'status' => 'ACKNOWLEDGED',
    ]);

    $this->post(route('security-alerts.acknowledge', $alert))->assertRedirect();

    expect($alert->histories()->count())->toBe(0);
});

it('records resolution and clears lifecycle timestamps when reopened', function () {
    $alert = SecurityAlert::query()->create([
        'severity' => 'CRITICAL',
        'title' => 'Dangerous database operation',
        'status' => 'OPEN',
    ]);

    $this->post(route('security-alerts.resolve', $alert), [
        'resolution_note' => 'Operation reviewed and access revoked.',
    ])->assertRedirect();

    $resolvedAlert = $alert->fresh();

    expect($resolvedAlert->status)->toBe('RESOLVED')
        ->and($resolvedAlert->acknowledged_at)->not->toBeNull()
        ->and($resolvedAlert->resolved_at)->not->toBeNull()
        ->and($resolvedAlert->histories()->count())->toBe(1);

    $this->post(route('security-alerts.reopen', $alert))->assertRedirect();

    $reopenedAlert = $alert->fresh();

    expect($reopenedAlert->status)->toBe('OPEN')
        ->and($reopenedAlert->acknowledged_at)->toBeNull()
        ->and($reopenedAlert->resolved_at)->toBeNull()
        ->and($reopenedAlert->resolution_note)->toBeNull()
        ->and($reopenedAlert->histories()->count())->toBe(2);
});
