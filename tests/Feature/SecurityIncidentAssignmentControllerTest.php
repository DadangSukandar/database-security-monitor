<?php

use App\Enums\TeamRole;
use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\Team;
use App\Models\User;

function createIncidentAssignmentControllerIncident(
    Team $team,
    array $attributes = []
): SecurityIncident {
    $creator = User::factory()->create();

    $alert = new SecurityAlert([
        'alert_type' => 'VULNERABILITY',
        'severity' => 'HIGH',
        'title' => 'Incident assignment source alert',
        'description' => 'Source alert for incident assignment.',
        'status' => 'OPEN',
        'detected_at' => now()
            ->subHour()
            ->startOfSecond(),
        'sla_started_at' => now()
            ->subHour()
            ->startOfSecond(),
        'occurrence_count' => 1,
        'first_seen_at' => now()
            ->subHour()
            ->startOfSecond(),
        'last_seen_at' => now()
            ->subHour()
            ->startOfSecond(),
    ]);

    $alert->team_id =
        $team->id;

    $alert->save();

    $incident = new SecurityIncident(
        array_merge([
            'incident_number' => sprintf(
                'INC-%s-%04d',
                now()->format('Ymd'),
                SecurityIncident::query()->count() + 1
            ),

            'security_alert_id' => $alert->id,

            'title' => 'Incident assignment test',

            'description' => 'Incident used for assignment controller test.',

            'severity' => 'HIGH',

            'status' => 'OPEN',

            'created_by_user_id' => $creator->id,

            'opened_at' => now(),
        ], $attributes)
    );

    $incident->team_id =
        $team->id;

    $incident->save();

    return $incident;
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

    $incident =
    createIncidentAssignmentControllerIncident(
        $team
    );

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

    $incident =
    createIncidentAssignmentControllerIncident(
        $team,
        [
            'assigned_to_user_id' => $firstAssignee->id,

            'assigned_at' => now(),
        ]
    );

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

    $incident =
    createIncidentAssignmentControllerIncident(
        $team
    );

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

    $incident =
    createIncidentAssignmentControllerIncident(
        $team,
        [
            'assigned_to_user_id' => $assignee->id,

            'assigned_at' => now(),
        ]
    );

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

    $acknowledgedAt =
        now()
            ->subHours(2)
            ->startOfSecond();

    $investigationStartedAt =
        now()
            ->subHour()
            ->startOfSecond();

    $incident =
    createIncidentAssignmentControllerIncident(
        $team,
        [
            'status' => 'INVESTIGATING',

            'acknowledged_at' => $acknowledgedAt,

            'investigation_started_at' => $investigationStartedAt,
        ]
    );

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
    $assignee =
        User::factory()->create();

    $team =
        Team::factory()->create();

    $incident =
        createIncidentAssignmentControllerIncident(
            $team
        );

    $assignedIncident =
        createIncidentAssignmentControllerIncident(
            $team,
            [
                'assigned_to_user_id' => $assignee->id,

                'assigned_at' => now(),
            ]
        );

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
