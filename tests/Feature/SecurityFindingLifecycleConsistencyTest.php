<?php

use App\Models\SecurityFinding;
use App\Models\User;

it('records the same immutable finding history through security audit lifecycle routes', function () {
    $user = User::factory()->create();

    $finding = SecurityFinding::query()->create([
        'finding_type' => 'WEAK_PRIVILEGE',
        'category' => 'PRIVILEGE',
        'severity' => 'HIGH',
        'title' => 'Privilege review required',
        'status' => 'OPEN',
    ]);

    $this->actingAs($user)
        ->post(route('security-audit.resolve', $finding))
        ->assertRedirect();

    $finding->refresh();

    expect($finding->status)->toBe('RESOLVED')
        ->and($finding->resolved_at)->not->toBeNull();

    $this->assertDatabaseHas('security_finding_histories', [
        'security_finding_id' => $finding->id,
        'action' => 'RESOLVE',
        'old_status' => 'OPEN',
        'new_status' => 'RESOLVED',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('security-audit.reopen', $finding))
        ->assertRedirect();

    $finding->refresh();

    expect($finding->status)->toBe('OPEN')
        ->and($finding->resolved_at)->toBeNull()
        ->and($finding->histories()->count())->toBe(2);
});
