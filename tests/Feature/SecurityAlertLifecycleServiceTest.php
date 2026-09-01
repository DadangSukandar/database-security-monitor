<?php

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;
use App\Services\SecurityAlertLifecycleService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function createLifecycleAlert(array $attributes = []): SecurityAlert
{
    return SecurityAlert::query()->create(array_merge([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'HIGH',
        'title' => 'Lifecycle foundation alert',
        'status' => 'OPEN',
        'detected_at' => now()->subHour()->startOfSecond(),
        'first_seen_at' => now()->subHour()->startOfSecond(),
        'last_seen_at' => now()->subHour()->startOfSecond(),
    ], $attributes));
}

beforeEach(function () {
    Notification::fake();
    config()->set('services.security_alerts.recipients', []);
});

it('transitions open to acknowledged and records its history atomically', function () {
    $alert = createLifecycleAlert();
    $transitionedAt = now()->startOfSecond();

    app(SecurityAlertLifecycleService::class)->acknowledge($alert, null, $transitionedAt);

    expect($alert->fresh()->status)->toBe('ACKNOWLEDGED')
        ->and($alert->fresh()->acknowledged_at->equalTo($transitionedAt))->toBeTrue();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'ACKNOWLEDGE',
        'old_status' => 'OPEN',
        'new_status' => 'ACKNOWLEDGED',
    ]);
});

it('supports investigating and resolution through explicit transitions', function () {
    $alert = createLifecycleAlert();
    $lifecycle = app(SecurityAlertLifecycleService::class);

    $lifecycle->investigate($alert, 'Reviewing database privileges.');
    $lifecycle->resolve($alert->fresh(), 'Unnecessary privilege removed.');

    $resolved = $alert->fresh();

    expect($resolved->status)->toBe('RESOLVED')
        ->and($resolved->acknowledged_at)->not->toBeNull()
        ->and($resolved->resolved_at)->not->toBeNull()
        ->and($resolved->resolution_note)->toBe('Unnecessary privilege removed.')
        ->and($resolved->histories()->pluck('action')->all())
        ->toContain('START_INVESTIGATION', 'RESOLVE');
});

it('starts investigation through the controller and records the authenticated user', function () {
    $user = User::factory()->create();

    $alert = createLifecycleAlert();

    $this->actingAs($user)
        ->post(route('security-alerts.investigate', $alert), [
            'investigation_note' => 'Reviewing excessive database privileges.',
        ])
        ->assertSessionHasNoErrors();

    $investigating = $alert->fresh();

    expect($investigating->status)->toBe('INVESTIGATING')
        ->and($investigating->acknowledged_at)->not->toBeNull();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'START_INVESTIGATION',
        'old_status' => 'OPEN',
        'new_status' => 'INVESTIGATING',
        'notes' => 'Reviewing excessive database privileges.',
        'user_id' => $user->id,
    ]);
});

it('starts investigation from acknowledged status and keeps the authenticated actor', function () {
    $user = User::factory()->create();

    $alert = createLifecycleAlert();

    app(SecurityAlertLifecycleService::class)
        ->acknowledge($alert);

    $acknowledgedAt = $alert->fresh()->acknowledged_at;

    $this->actingAs($user)
        ->post(route('security-alerts.investigate', $alert), [
            'investigation_note' => 'Investigating privilege escalation.',
        ])
        ->assertSessionHasNoErrors();

    $investigating = $alert->fresh();

    expect($investigating->status)->toBe('INVESTIGATING')
        ->and($investigating->acknowledged_at->equalTo($acknowledgedAt))->toBeTrue();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'START_INVESTIGATION',
        'old_status' => 'ACKNOWLEDGED',
        'new_status' => 'INVESTIGATING',
        'notes' => 'Investigating privilege escalation.',
        'user_id' => $user->id,
    ]);
});

it('rejects investigation for resolved and historical duplicate alerts', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $canonical = createLifecycleAlert();

    app(SecurityAlertLifecycleService::class)->resolve(
        $canonical,
        'Issue remediated.'
    );

    $duplicate = createLifecycleAlert([
        'canonical_alert_id' => $canonical->id,
        'consolidated_at' => now(),
    ]);

    $this->post(
        route('security-alerts.investigate', $canonical),
        [
            'investigation_note' => 'Should not be allowed.',
        ]
    )->assertSessionHasErrors('alert');

    $this->post(
        route('security-alerts.investigate', $duplicate),
        [
            'investigation_note' => 'Should not be allowed.',
        ]
    )->assertSessionHasErrors('alert');

    expect($canonical->fresh()->status)->toBe('RESOLVED')
        ->and($duplicate->fresh()->status)->toBe('OPEN')
        ->and(
            $canonical->histories()
                ->where('action', 'START_INVESTIGATION')
                ->count()
        )->toBe(0)
        ->and(
            $duplicate->histories()
                ->where('action', 'START_INVESTIGATION')
                ->count()
        )->toBe(0);
});

it('manually reopens only resolved alerts and restarts the current SLA cycle', function () {
    $firstSeenAt = now()->subDay()->startOfSecond();
    $lastSeenAt = now()->subHours(2)->startOfSecond();
    $reopenedAt = now()->startOfSecond();
    $alert = createLifecycleAlert([
        'status' => 'RESOLVED',
        'first_seen_at' => $firstSeenAt,
        'last_seen_at' => $lastSeenAt,
        'acknowledged_at' => now()->subHours(3),
        'resolved_at' => now()->subHours(2),
        'resolution_note' => 'Previous remediation evidence.',
    ]);

    app(SecurityAlertLifecycleService::class)->reopen($alert, null, $reopenedAt);

    $reopened = $alert->fresh();

    expect($reopened->status)->toBe('OPEN')
        ->and($reopened->acknowledged_at)->toBeNull()
        ->and($reopened->resolved_at)->toBeNull()
        ->and($reopened->resolution_note)->toBeNull()
        ->and($reopened->first_seen_at->equalTo($firstSeenAt))->toBeTrue()
        ->and($reopened->last_seen_at->equalTo($lastSeenAt))->toBeTrue()
        ->and($reopened->sla_started_at->equalTo($reopenedAt))->toBeTrue()
        ->and($reopened->responseSlaStatus($reopenedAt))->toBe('ON_TRACK');

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'REOPEN',
        'old_status' => 'RESOLVED',
        'new_status' => 'OPEN',
    ]);
});

it('rejects invalid and repeated lifecycle transitions', function () {
    $alert = createLifecycleAlert();
    $lifecycle = app(SecurityAlertLifecycleService::class);
    $lifecycle->acknowledge($alert);

    expect(fn () => $lifecycle->acknowledge($alert->fresh()))
        ->toThrow(DomainException::class, 'ACKNOWLEDGED -> ACKNOWLEDGED')
        ->and(fn () => $lifecycle->reopen($alert->fresh()))
        ->toThrow(DomainException::class, 'ACKNOWLEDGED -> OPEN')
        ->and($alert->histories()->count())->toBe(1);
});

it('rolls back the alert update when history creation fails', function () {
    $alert = createLifecycleAlert();
    $before = $alert->fresh()->getAttributes();

    DB::statement(<<<'SQL'
        CREATE TRIGGER fail_security_alert_history
        BEFORE INSERT ON security_alert_histories
        BEGIN
            SELECT RAISE(ABORT, 'forced history failure');
        END
    SQL);

    try {
        expect(fn () => app(SecurityAlertLifecycleService::class)->acknowledge($alert))
            ->toThrow(QueryException::class);
    } finally {
        DB::statement('DROP TRIGGER IF EXISTS fail_security_alert_history');
    }

    expect($alert->fresh()->getAttributes())->toBe($before)
        ->and(SecurityAlertHistory::query()->count())->toBe(0);
});

it('rejects lifecycle changes to historical duplicate alerts', function () {
    $canonical = createLifecycleAlert();
    $duplicate = createLifecycleAlert([
        'canonical_alert_id' => $canonical->id,
        'consolidated_at' => now(),
    ]);
    $before = $duplicate->fresh()->getAttributes();

    $user = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('security-alerts.acknowledge', $duplicate))
        ->assertSessionHasErrors('alert');

    $this->post(route('security-alerts.acknowledge', $canonical))
        ->assertSessionHasNoErrors();

    expect($duplicate->fresh()->getAttributes())->toBe($before)
        ->and($duplicate->histories()->count())->toBe(0);

    expect($canonical->fresh()->status)->toBe('ACKNOWLEDGED')
        ->and($canonical->histories()->where('action', 'ACKNOWLEDGE')->count())->toBe(1);
});

it('rejects invalid transitions from a resolved alert', function () {
    $alert = createLifecycleAlert();

    $lifecycle = app(SecurityAlertLifecycleService::class);

    $lifecycle->resolve(
        $alert,
        'Issue remediated.'
    );

    $resolved = $alert->fresh();

    expect($resolved->status)->toBe('RESOLVED')
        ->and(fn () => $lifecycle->acknowledge($resolved))
        ->toThrow(
            DomainException::class,
            'RESOLVED -> ACKNOWLEDGED'
        )
        ->and(fn () => $lifecycle->resolve(
            $resolved,
            'Second resolution attempt.'
        ))
        ->toThrow(
            DomainException::class,
            'RESOLVED -> RESOLVED'
        );

    expect($alert->fresh()->status)->toBe('RESOLVED')
        ->and(
            $alert->histories()
                ->where('action', 'RESOLVE')
                ->count()
        )->toBe(1);
});

it('prevents guests from mutating the security alert lifecycle', function () {
    $open = createLifecycleAlert([
        'status' => 'OPEN',
        'canonical_alert_id' => null,
    ]);

    $resolved = createLifecycleAlert([
        'status' => 'RESOLVED',
        'canonical_alert_id' => null,
        'resolved_at' => now(),
        'resolution_note' => 'Previously resolved.',
    ]);

    $this->post(
        route('security-alerts.acknowledge', $open)
    )->assertRedirect();

    $this->post(
        route('security-alerts.investigate', $open),
        [
            'investigation_note' => 'Unauthorized investigation.',
        ]
    )->assertRedirect();

    $this->post(
        route('security-alerts.resolve', $open),
        [
            'resolution_note' => 'Unauthorized resolution.',
        ]
    )->assertRedirect();

    $this->post(
        route('security-alerts.reopen', $resolved)
    )->assertRedirect();

    $open->refresh();
    $resolved->refresh();

    expect($open->status)
        ->toBe('OPEN')
        ->and($resolved->status)
        ->toBe('RESOLVED');

    expect(
        SecurityAlertHistory::query()
            ->whereIn('security_alert_id', [
                $open->id,
                $resolved->id,
            ])
            ->count()
    )->toBe(0);
});
