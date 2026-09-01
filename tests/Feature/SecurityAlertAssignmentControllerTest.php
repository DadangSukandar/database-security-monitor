<?php

use App\Enums\TeamRole;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\Team;
use App\Models\User;

function createAssignmentControllerAlert(array $attributes = []): SecurityAlert
{
    return SecurityAlert::query()->create(array_merge([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'HIGH',
        'title' => 'Assignment controller alert',
        'status' => 'OPEN',
        'detected_at' => now()->subHour()->startOfSecond(),
        'first_seen_at' => now()->subHour()->startOfSecond(),
        'last_seen_at' => now()->subHour()->startOfSecond(),
    ], $attributes));
}

function attachUserToTeam(
    Team $team,
    User $user,
    TeamRole $role = TeamRole::Member
): void {
    $team->members()->attach($user, [
        'role' => $role->value,
    ]);
}

it('assigns an alert to a member of the current team', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachUserToTeam($team, $actor);
    attachUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $alert = createAssignmentControllerAlert();

    $this->actingAs($actor)
        ->post(route('security-alerts.assign', $alert), [
            'assigned_to_user_id' => $assignee->id,
        ])
        ->assertSessionHasNoErrors();

    $assigned = $alert->fresh();

    expect($assigned->assigned_to_user_id)
        ->toBe($assignee->id)
        ->and($assigned->assigned_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'ASSIGN',
        'user_id' => $actor->id,
    ]);
});

it('reassigns an alert to another member of the current team', function () {
    $actor = User::factory()->create();
    $firstAssignee = User::factory()->create();
    $secondAssignee = User::factory()->create();
    $team = Team::factory()->create();

    attachUserToTeam($team, $actor);
    attachUserToTeam($team, $firstAssignee);
    attachUserToTeam($team, $secondAssignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $alert = createAssignmentControllerAlert([
        'assigned_to_user_id' => $firstAssignee->id,
        'assigned_at' => now()->subHour(),
    ]);

    $this->actingAs($actor)
        ->post(route('security-alerts.assign', $alert), [
            'assigned_to_user_id' => $secondAssignee->id,
        ])
        ->assertSessionHasNoErrors();

    expect($alert->fresh()->assigned_to_user_id)
        ->toBe($secondAssignee->id);

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'REASSIGN',
        'user_id' => $actor->id,
    ]);
});

it('rejects assigning an alert to a user outside the current team', function () {
    $actor = User::factory()->create();
    $outsider = User::factory()->create();
    $team = Team::factory()->create();

    attachUserToTeam($team, $actor);

    expect($actor->switchTeam($team))->toBeTrue();

    $alert = createAssignmentControllerAlert();

    $this->actingAs($actor)
        ->post(route('security-alerts.assign', $alert), [
            'assigned_to_user_id' => $outsider->id,
        ])
        ->assertSessionHasErrors('assigned_to_user_id');

    expect($alert->fresh()->assigned_to_user_id)
        ->toBeNull();

    expect(
        SecurityAlertHistory::query()
            ->where('security_alert_id', $alert->id)
            ->whereIn('action', ['ASSIGN', 'REASSIGN'])
            ->count()
    )->toBe(0);
});

it('unassigns an alert and records the authenticated actor', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachUserToTeam($team, $actor);
    attachUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $alert = createAssignmentControllerAlert([
        'assigned_to_user_id' => $assignee->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->post(route('security-alerts.unassign', $alert))
        ->assertSessionHasNoErrors();

    $unassigned = $alert->fresh();

    expect($unassigned->assigned_to_user_id)
        ->toBeNull()
        ->and($unassigned->assigned_at)
        ->toBeNull();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'UNASSIGN',
        'user_id' => $actor->id,
    ]);
});

it('rejects assignment changes to historical duplicate alerts', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachUserToTeam($team, $actor);
    attachUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $canonical = createAssignmentControllerAlert();

    $duplicate = createAssignmentControllerAlert([
        'canonical_alert_id' => $canonical->id,
        'consolidated_at' => now(),
    ]);

    $this->actingAs($actor)
        ->post(route('security-alerts.assign', $duplicate), [
            'assigned_to_user_id' => $assignee->id,
        ])
        ->assertSessionHasErrors('alert');

    expect($duplicate->fresh()->assigned_to_user_id)
        ->toBeNull();
});

it('prevents guests from assigning and unassigning alerts', function () {
    $assignee = User::factory()->create();

    $alert = createAssignmentControllerAlert();

    $assignedAlert = createAssignmentControllerAlert([
        'assigned_to_user_id' => $assignee->id,
        'assigned_at' => now(),
    ]);

    $this->post(route('security-alerts.assign', $alert), [
        'assigned_to_user_id' => $assignee->id,
    ])->assertRedirect();

    $this->post(
        route('security-alerts.unassign', $assignedAlert)
    )->assertRedirect();

    expect($alert->fresh()->assigned_to_user_id)
        ->toBeNull()
        ->and($assignedAlert->fresh()->assigned_to_user_id)
        ->toBe($assignee->id);
});
