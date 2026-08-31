<?php

use App\Models\SecurityAlert;
use App\Notifications\SecurityAlertCreatedNotification;
use App\Notifications\SecurityAlertSlaBreachedNotification;
use App\Services\SecurityAlertLifecycleService;
use Illuminate\Support\Facades\Notification;

it('notifies configured recipients when a high or critical alert is created', function () {
    Notification::fake();
    config()->set('services.security_alerts.recipients', ['security@example.com']);

    SecurityAlert::query()->create([
        'alert_type' => 'DANGEROUS_DDL',
        'severity' => 'CRITICAL',
        'title' => 'Critical database operation',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    SecurityAlert::query()->create([
        'alert_type' => 'SELECT_STAR',
        'severity' => 'LOW',
        'title' => 'Low priority query',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    Notification::assertSentOnDemand(SecurityAlertCreatedNotification::class);
    Notification::assertCount(1);
});

it('escalates an SLA breach only once', function () {
    config()->set('services.security_alerts.recipients', []);

    $alert = SecurityAlert::query()->create([
        'alert_type' => 'DANGEROUS_DDL',
        'severity' => 'CRITICAL',
        'title' => 'Overdue critical alert',
        'status' => 'OPEN',
        'detected_at' => now()->subMinutes(30),
    ]);

    Notification::fake();
    config()->set('services.security_alerts.recipients', ['security@example.com']);

    $this->artisan('security:escalate-alerts')
        ->expectsOutput('Escalated 1 security alert(s).')
        ->assertSuccessful();

    $this->artisan('security:escalate-alerts')
        ->expectsOutput('Escalated 0 security alert(s).')
        ->assertSuccessful();

    Notification::assertSentOnDemand(SecurityAlertSlaBreachedNotification::class);
    Notification::assertCount(1);

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'SLA_ESCALATION',
    ]);
});

it('does not notify or mark escalation when no recipients are configured', function () {
    Notification::fake();
    config()->set('services.security_alerts.recipients', []);

    $alert = SecurityAlert::query()->create([
        'alert_type' => 'DANGEROUS_DDL',
        'severity' => 'HIGH',
        'title' => 'Unrouted security alert',
        'status' => 'OPEN',
        'detected_at' => now()->subHours(2),
    ]);

    $this->artisan('security:escalate-alerts')->assertSuccessful();

    Notification::assertNothingSent();
    expect($alert->histories()->count())->toBe(0);
});

it('allows a new SLA escalation after a resolved alert starts a new lifecycle', function () {
    Notification::fake();
    config()->set('services.security_alerts.recipients', []);
    $firstCycleNow = now()->startOfSecond();

    $alert = SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'CRITICAL',
        'title' => 'Recurring overdue alert',
        'status' => 'OPEN',
        'detected_at' => $firstCycleNow->copy()->subHours(2),
        'sla_started_at' => $firstCycleNow->copy()->subHours(2),
    ]);

    config()->set('services.security_alerts.recipients', ['security@example.com']);

    $this->artisan('security:escalate-alerts')
        ->expectsOutput('Escalated 1 security alert(s).')
        ->assertSuccessful();

    $lifecycle = app(SecurityAlertLifecycleService::class);
    $lifecycle->resolve($alert->fresh(), 'First lifecycle remediated.', null, $firstCycleNow->copy()->addSeconds(30));
    $reopenedAt = $firstCycleNow->copy()->addMinute();
    $lifecycle->reopen($alert->fresh(), null, $reopenedAt);
    $this->travelTo($reopenedAt->copy()->addMinutes(30));

    $this->artisan('security:escalate-alerts')
        ->expectsOutput('Escalated 1 security alert(s).')
        ->assertSuccessful();

    expect($alert->histories()->where('action', 'SLA_ESCALATION')->count())->toBe(2);
});
