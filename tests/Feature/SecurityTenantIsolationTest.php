<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createAlertForTeam(
        Team $team,
        array $attributes = []
    ): SecurityAlert {
        $alert = new SecurityAlert(
            array_merge([
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'title' => 'Test Security Alert',
                'detected_at' => now(),
            ], $attributes)
        );

        $alert->team_id = $team->id;

        $alert->save();

        return $alert;
    }

    private function createIncidentForTeam(
        Team $team,
        SecurityAlert $alert,
        array $attributes = []
    ): SecurityIncident {
        $incident = new SecurityIncident(
            array_merge([
                'security_alert_id' => $alert->id,
                'incident_number' => 'INC-TEST-'.uniqid(),
                'title' => 'Test Security Incident',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'opened_at' => now(),
            ], $attributes)
        );

        $incident->team_id = $team->id;

        $incident->save();

        return $incident;
    }

    public function test_current_team_does_not_see_alert_from_other_team(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alertA = $this->createAlertForTeam(
            $teamA,
            [
                'title' => 'Team A Alert',
            ]
        );

        $alertB = $this->createAlertForTeam(
            $teamB,
            [
                'title' => 'Team B Alert',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('security-alerts.index'));

        $response
            ->assertOk()
            ->assertSee('Team A Alert')
            ->assertDontSee('Team B Alert');
    }

    public function test_current_team_does_not_see_incident_from_other_team(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alertA = $this->createAlertForTeam(
            $teamA,
            [
                'title' => 'Team A Alert',
            ]
        );

        $alertB = $this->createAlertForTeam(
            $teamB,
            [
                'title' => 'Team B Alert',
            ]
        );

        $incidentA = $this->createIncidentForTeam(
            $teamA,
            $alertA,
            [
                'incident_number' => 'INC-TEAM-A',
                'title' => 'Team A Incident',
            ]
        );

        $incidentB = $this->createIncidentForTeam(
            $teamB,
            $alertB,
            [
                'incident_number' => 'INC-TEAM-B',
                'title' => 'Team B Incident',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSee('Team A Incident')
            ->assertDontSee('Team B Incident');
    }

    public function test_cross_team_incident_detail_is_not_accessible(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alert = new SecurityAlert([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'title' => 'Foreign Alert',
            'detected_at' => now(),
        ]);

        $alert->team_id = $teamB->id;

        $alert->save();

        $incident = $this->createIncidentForTeam(
            $teamB,
            $alert,
            [
                'incident_number' => 'INC-FOREIGN-DETAIL',
                'title' => 'Foreign Incident',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.show', $incident));

        $response->assertNotFound();
    }

    public function test_cross_team_incident_acknowledge_is_not_accessible(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alert = new SecurityAlert([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'title' => 'Foreign Alert',
            'detected_at' => now(),
        ]);

        $alert->team_id = $teamB->id;

        $alert->save();

        $incident = $this->createIncidentForTeam(
            $teamB,
            $alert,
            [
                'incident_number' => 'INC-FOREIGN-ACK',
                'title' => 'Foreign Incident',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'security-incidents.acknowledge',
                    $incident
                )
            );

        $response->assertNotFound();

        $incident->refresh();

        $this->assertSame(
            'OPEN',
            $incident->status
        );
    }

    public function test_cross_team_incident_assignment_is_not_accessible(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $teamA->members()->attach($assignee->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alert = new SecurityAlert([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'title' => 'Foreign Alert',
            'detected_at' => now(),
        ]);

        $alert->team_id = $teamB->id;

        $alert->save();

        $incident = $this->createIncidentForTeam(
            $teamB,
            $alert,
            [
                'incident_number' => 'INC-FOREIGN-ASSIGN',
                'title' => 'Foreign Incident',
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'security-incidents.assign',
                    $incident
                ),
                [
                    'assigned_to_user_id' => $assignee->id,
                ]
            );

        $response->assertNotFound();

        $incident->refresh();

        $this->assertNull(
            $incident->assigned_to_user_id
        );
    }

    public function test_cross_team_alert_detail_is_not_accessible(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alert = new SecurityAlert([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'title' => 'Foreign Alert',
            'detected_at' => now(),
        ]);

        $alert->team_id = $teamB->id;

        $alert->save();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'security-alerts.show',
                    $alert
                )
            );

        $response->assertNotFound();
    }

    public function test_cross_team_alert_acknowledge_is_not_accessible(): void
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        $alert = new SecurityAlert([
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'title' => 'Foreign Alert',
            'detected_at' => now(),
        ]);

        $alert->team_id = $teamB->id;

        $alert->save();

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'security-alerts.acknowledge',
                    $alert
                )
            );

        $response->assertNotFound();

        $alert->refresh();

        $this->assertSame(
            'OPEN',
            $alert->status
        );
    }
}
