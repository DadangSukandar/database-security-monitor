<?php

use App\Models\SecurityAlert;
use App\Models\User;

it('assigns response targets based on alert severity', function (string $severity, int $minutes) {
    $alert = new SecurityAlert([
        'severity' => $severity,
        'detected_at' => now(),
    ]);

    expect($alert->responseSlaMinutes())->toBe($minutes);
})->with([
    ['CRITICAL', 15],
    ['HIGH', 60],
    ['MEDIUM', 240],
    ['LOW', 1440],
]);

it('identifies breached due soon and met response targets', function () {
    $now = now();

    $breached = new SecurityAlert([
        'severity' => 'CRITICAL',
        'status' => 'OPEN',
        'detected_at' => $now->copy()->subMinutes(20),
    ]);

    $dueSoon = new SecurityAlert([
        'severity' => 'CRITICAL',
        'status' => 'OPEN',
        'detected_at' => $now->copy()->subMinutes(12),
    ]);

    $met = new SecurityAlert([
        'severity' => 'HIGH',
        'status' => 'ACKNOWLEDGED',
        'detected_at' => $now->copy()->subMinutes(30),
        'acknowledged_at' => $now->copy()->subMinutes(10),
    ]);

    expect($breached->responseSlaStatus($now))->toBe('BREACHED')
        ->and($dueSoon->responseSlaStatus($now))->toBe('DUE_SOON')
        ->and($met->responseSlaStatus($now))->toBe('MET');
});

it('shows SLA escalation counts on the security dashboard', function () {
    $this->actingAs(User::factory()->create());

    SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'CRITICAL',
        'title' => 'Overdue critical alert',
        'status' => 'OPEN',
        'detected_at' => now()->subMinutes(30),
    ]);

    SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'CRITICAL',
        'title' => 'Critical alert due soon',
        'status' => 'OPEN',
        'detected_at' => now()->subMinutes(12),
    ]);

    $this->get(route('security-dashboard'))
        ->assertOk()
        ->assertViewHas('breachedSlaAlerts', 1)
        ->assertViewHas('dueSoonSlaAlerts', 1);
});

it('displays response SLA on alert list and detail pages', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $alert = SecurityAlert::query()->create([
        'alert_type' => 'DANGEROUS_DDL',
        'severity' => 'CRITICAL',
        'title' => 'Critical response required',
        'status' => 'OPEN',
        'detected_at' => now()->subMinutes(30),
    ]);

    $this->get(route('security-alerts.index'))
        ->assertOk()
        ->assertSee('RESPONSE SLA')
        ->assertSee('BREACHED');

    $this->get(route('security-alerts.show', $alert))
        ->assertOk()
        ->assertSee('Response SLA: BREACHED');
});
