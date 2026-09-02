<?php

use App\Enums\TeamRole;
use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\Team;
use App\Models\User;

function createIncidentAssignmentControllerIncident(
    array $attributes = []
): SecurityIncident {
    $creator = User::factory()->create();

    $alert = SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'HIGH',
        'title' => 'Incident assignment source alert',
        'description' => 'Source alert for incident assignment.',
        'status' => 'OPEN',
        'detected_at' => now()->subHour()->startOfSecond(),
        'sla_started_at' => now()->subHour()->startOfSecond(),
        'occurrence_count' => 1,
        'first_seen_at' => now()->subHour()->startOfSecond(),
        'last_seen_at' => now()->subHour()->startOfSecond(),
    ]);

    return SecurityIncident::query()->create(
        array_merge([
            'incident_number' => 'INC-'.now()->format('Ymd').'-9999',

            'security_alert_id' => $alert->id,

            'title' => 'Incident assignment test',

            'description' => 'Incident used for assignment controller test.',

            'severity' => 'HIGH',

            'status' => 'OPEN',

            'created_by_user_id' => $creator->id,

            'opened_at' => now(),
        ], $attributes)
    );
}

function attachIncidentUserToTeam(
    Team $team,
    User $user,
    TeamRole $role = TeamRole::Member
): void {
    $team->members()->attach($user, [
        'role' => $role->value,
    ]);
}

it('assigns an incident to a member of the current team', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachIncidentUserToTeam($team, $actor);
    attachIncidentUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $incident = createIncidentAssignmentControllerIncident();

    $this->actingAs($actor)
        ->post(
            route(
                'security-incidents.assign',
                $incident
            ),
            [
                'assigned_to_user_id' => $assignee->id,
            ]
        )
        ->assertSessionHasNoErrors();

    $assigned = $incident->fresh();

    expect($assigned->assigned_to_user_id)
        ->toBe($assignee->id)
        ->and($assigned->assigned_at)
        ->not->toBeNull();

    $this->assertDatabaseHas(
        'security_incident_histories',
        [
            'security_incident_id' => $incident->id,
            'action' => 'ASSIGN',
            'old_status' => 'OPEN',
            'new_status' => 'OPEN',
            'user_id' => $actor->id,
        ]
    );
});

it('reassigns an incident to another member of the current team', function () {
    $actor = User::factory()->create();
    $firstAssignee = User::factory()->create();
    $secondAssignee = User::factory()->create();
    $team = Team::factory()->create();

    attachIncidentUserToTeam($team, $actor);
    attachIncidentUserToTeam(
        $team,
        $firstAssignee
    );
    attachIncidentUserToTeam(
        $team,
        $secondAssignee
    );

    expect($actor->switchTeam($team))->toBeTrue();

    $incident = createIncidentAssignmentControllerIncident([
        'assigned_to_user_id' => $firstAssignee->id,
        'assigned_at' => now()->subHour(),
    ]);

    $this->actingAs($actor)
        ->post(
            route(
                'security-incidents.assign',
                $incident
            ),
            [
                'assigned_to_user_id' => $secondAssignee->id,
            ]
        )
        ->assertSessionHasNoErrors();

    expect($incident->fresh()->assigned_to_user_id)
        ->toBe($secondAssignee->id);

    $this->assertDatabaseHas(
        'security_incident_histories',
        [
            'security_incident_id' => $incident->id,
            'action' => 'REASSIGN',
            'user_id' => $actor->id,
        ]
    );
});

it('rejects assigning an incident to a user outside the current team', function () {
    $actor = User::factory()->create();
    $outsider = User::factory()->create();
    $team = Team::factory()->create();

    attachIncidentUserToTeam($team, $actor);

    expect($actor->switchTeam($team))->toBeTrue();

    $incident = createIncidentAssignmentControllerIncident();

    $this->actingAs($actor)
        ->post(
            route(
                'security-incidents.assign',
                $incident
            ),
            [
                'assigned_to_user_id' => $outsider->id,
            ]
        )
        ->assertSessionHasErrors(
            'assigned_to_user_id'
        );

    expect($incident->fresh()->assigned_to_user_id)
        ->toBeNull();

    expect(
        SecurityIncidentHistory::query()
            ->where(
                'security_incident_id',
                $incident->id
            )
            ->whereIn(
                'action',
                [
                    'ASSIGN',
                    'REASSIGN',
                ]
            )
            ->count()
    )->toBe(0);
});

it('unassigns an incident and records the authenticated actor', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachIncidentUserToTeam($team, $actor);
    attachIncidentUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $incident = createIncidentAssignmentControllerIncident([
        'assigned_to_user_id' => $assignee->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($actor)
        ->post(
            route(
                'security-incidents.unassign',
                $incident
            )
        )
        ->assertSessionHasNoErrors();

    $unassigned = $incident->fresh();

    expect($unassigned->assigned_to_user_id)
        ->toBeNull()
        ->and($unassigned->assigned_at)
        ->toBeNull();

    $this->assertDatabaseHas(
        'security_incident_histories',
        [
            'security_incident_id' => $incident->id,
            'action' => 'UNASSIGN',
            'user_id' => $actor->id,
        ]
    );
});

it('assignment does not change incident lifecycle', function () {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team = Team::factory()->create();

    attachIncidentUserToTeam($team, $actor);
    attachIncidentUserToTeam($team, $assignee);

    expect($actor->switchTeam($team))->toBeTrue();

    $investigationStartedAt =
        now()->subHour()->startOfSecond();

    $incident = createIncidentAssignmentControllerIncident([
        'status' => 'INVESTIGATING',
        'acknowledged_at' => now()->subHours(2)->startOfSecond(),

        'investigation_started_at' => $investigationStartedAt,
    ]);

    $this->actingAs($actor)
        ->post(
            route(
                'security-incidents.assign',
                $incident
            ),
            [
                'assigned_to_user_id' => $assignee->id,
            ]
        )
        ->assertSessionHasNoErrors();

    $incident->refresh();

    expect($incident->status)
        ->toBe('INVESTIGATING')
        ->and(
            $incident
                ->investigation_started_at
                ?->equalTo($investigationStartedAt)
        )
        ->toBeTrue();

    $this->assertDatabaseHas(
        'security_incident_histories',
        [
            'security_incident_id' => $incident->id,
            'action' => 'ASSIGN',
            'old_status' => 'INVESTIGATING',
            'new_status' => 'INVESTIGATING',
            'user_id' => $actor->id,
        ]
    );
});

it('prevents guests from assigning and unassigning incidents', function () {
    $assignee = User::factory()->create();

    $incident = createIncidentAssignmentControllerIncident();

    $assignedIncident =
        createIncidentAssignmentControllerIncident([
            'incident_number' => 'INC-'.now()->format('Ymd').'-9998',

            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

    $this->post(
        route(
            'security-incidents.assign',
            $incident
        ),
        [
            'assigned_to_user_id' => $assignee->id,
        ]
    )->assertRedirect();

    $this->post(
        route(
            'security-incidents.unassign',
            $assignedIncident
        )
    )->assertRedirect();

    expect($incident->fresh()->assigned_to_user_id)
        ->toBeNull()
        ->and(
            $assignedIncident
                ->fresh()
                ->assigned_to_user_id
        )
        ->toBe($assignee->id);
});
