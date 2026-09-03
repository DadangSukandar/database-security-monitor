<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createAlert(
        array $attributes = []
    ): SecurityAlert {
        return SecurityAlert::query()->create(array_merge([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Unauthorized privilege activity',
            'description' => 'Suspicious privilege activity detected.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $alert = $this->createAlert();

        return SecurityIncident::query()->create(array_merge([
            'incident_number' => 'INC-20260902-0001',
            'security_alert_id' => $alert->id,
            'title' => 'Database privilege incident',
            'description' => 'Security incident for testing.',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now(),
        ], $attributes));
    }

    private function attachUserToTeam(
        Team $team,
        User $user,
        TeamRole $role = TeamRole::Member
    ): void {
        $team->members()->attach($user, [
            'role' => $role->value,
        ]);
    }

    private function createUserWithCurrentTeam(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->attachUserToTeam(
            $team,
            $user
        );

        $this->assertTrue(
            $user->switchTeam($team)
        );

        return [
            $user,
            $team,
        ];
    }

    public function test_authenticated_user_can_view_incident_index(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $incident = $this->createIncident();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSee($incident->incident_number)
            ->assertSee($incident->title);
    }

    public function test_it_searches_incident_number(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $matching = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'title' => 'First incident',
        ]);

        $other = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'title' => 'Second incident',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'search' => '0101',
            ]));

        $response
            ->assertOk()
            ->assertSee($matching->incident_number)
            ->assertDontSee($other->incident_number);
    }

    public function test_it_searches_incident_title(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $matching = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'title' => 'Suspicious database privilege escalation',
        ]);

        $other = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'title' => 'Authentication failure incident',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'search' => 'database privilege',
            ]));

        $response
            ->assertOk()
            ->assertSee($matching->incident_number)
            ->assertDontSee($other->incident_number);
    }

    public function test_search_term_is_trimmed(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $matching = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'title' => 'Database privilege incident',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'search' => '   Database privilege   ',
            ]));

        $response
            ->assertOk()
            ->assertSee($matching->incident_number);
    }

    public function test_it_filters_by_status(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $open = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'status' => 'OPEN',
        ]);

        $closed = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'status' => 'CLOSED',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'status' => 'CLOSED',
            ]));

        $response
            ->assertOk()
            ->assertSee($closed->incident_number)
            ->assertDontSee($open->incident_number);
    }

    public function test_it_filters_by_severity(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $critical = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'severity' => 'CRITICAL',
        ]);

        $low = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'severity' => 'LOW',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'severity' => 'CRITICAL',
            ]));

        $response
            ->assertOk()
            ->assertSee($critical->incident_number)
            ->assertDontSee($low->incident_number);
    }

    public function test_it_filters_by_current_team_pic(): void
    {
        [$user, $team] = $this->createUserWithCurrentTeam();

        $assignee = User::factory()->create();
        $otherAssignee = User::factory()->create();

        $this->attachUserToTeam(
            $team,
            $assignee
        );

        $this->attachUserToTeam(
            $team,
            $otherAssignee
        );

        $matching = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $other = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'assigned_to_user_id' => $otherAssignee->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'pic' => (string) $assignee->id,
            ]));

        $response
            ->assertOk()
            ->assertSee($matching->incident_number)
            ->assertDontSee($other->incident_number);
    }

    public function test_it_rejects_pic_outside_current_team(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $outsider = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'pic' => (string) $outsider->id,
            ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('pic');
    }

    public function test_it_rejects_non_numeric_pic_filter(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'pic' => 'invalid-user',
            ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('pic');
    }

    public function test_it_filters_unassigned_incidents(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $assignee = User::factory()->create();

        $unassigned = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        $assigned = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'pic' => 'unassigned',
            ]));

        $response
            ->assertOk()
            ->assertSee($unassigned->incident_number)
            ->assertDontSee($assigned->incident_number);
    }

    public function test_filters_can_be_combined(): void
    {
        [$user, $team] = $this->createUserWithCurrentTeam();

        $assignee = User::factory()->create();

        $this->attachUserToTeam(
            $team,
            $assignee
        );

        $matching = $this->createIncident([
            'incident_number' => 'INC-20260902-0101',
            'title' => 'Database privilege escalation',
            'severity' => 'CRITICAL',
            'status' => 'INVESTIGATING',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $wrongStatus = $this->createIncident([
            'incident_number' => 'INC-20260902-0202',
            'title' => 'Database privilege escalation',
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'search' => 'Database privilege',
                'status' => 'INVESTIGATING',
                'severity' => 'CRITICAL',
                'pic' => (string) $assignee->id,
            ]));

        $response
            ->assertOk()
            ->assertSee($matching->incident_number)
            ->assertDontSee($wrongStatus->incident_number);
    }

    public function test_it_rejects_invalid_status_filter(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'status' => 'INVALID_STATUS',
            ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    public function test_it_rejects_invalid_severity_filter(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'severity' => 'EXTREME',
            ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('severity');
    }

    public function test_pagination_preserves_active_filters(): void
    {
        [$user, $team] = $this->createUserWithCurrentTeam();

        $assignee = User::factory()->create();

        $this->attachUserToTeam(
            $team,
            $assignee
        );

        for ($i = 1; $i <= 21; $i++) {
            $this->createIncident([
                'incident_number' => sprintf(
                    'INC-20260902-%04d',
                    $i
                ),
                'title' => 'Database pagination incident '.$i,
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'assigned_to_user_id' => $assignee->id,
                'assigned_at' => now(),
                'opened_at' => now()->subSeconds($i),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'search' => 'Database pagination',
                'status' => 'OPEN',
                'severity' => 'HIGH',
                'pic' => (string) $assignee->id,
            ]));

        $response->assertOk();

        $response->assertSee(
            'search=Database%20pagination',
            false
        );

        $response->assertSee(
            'status=OPEN',
            false
        );

        $response->assertSee(
            'severity=HIGH',
            false
        );

        $response->assertSee(
            'pic='.$assignee->id,
            false
        );

        $response->assertSee(
            'page=2',
            false
        );
    }

    public function test_guest_cannot_view_incident_index(): void
    {
        $response = $this->get(
            route('security-incidents.index')
        );

        $response->assertRedirect();
    }

    public function test_it_filters_by_triage_priority(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $now = now();

        $p1 = $this->createIncident([
            'incident_number' => 'INC-PRIORITY-P1',
            'severity' => 'CRITICAL',
            'opened_at' => $now->copy()->subMinutes(5),
        ]);

        $p3 = $this->createIncident([
            'incident_number' => 'INC-PRIORITY-P3',
            'severity' => 'MEDIUM',
            'opened_at' => $now->copy()->subMinutes(30),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'priority' => 'P1',
            ]));

        $response
            ->assertOk()
            ->assertSee($p1->incident_number)
            ->assertDontSee($p3->incident_number);
    }

    public function test_it_filters_sla_promoted_priority(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $now = now();

        $breachedLow = $this->createIncident([
            'incident_number' => 'INC-BREACHED-P1',
            'severity' => 'LOW',
            'opened_at' => $now->copy()->subMinutes(1441),
        ]);

        $onTrackLow = $this->createIncident([
            'incident_number' => 'INC-LOW-P4',
            'severity' => 'LOW',
            'opened_at' => $now->copy()->subMinutes(30),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'priority' => 'P1',
            ]));

        $response
            ->assertOk()
            ->assertSee($breachedLow->incident_number)
            ->assertDontSee($onTrackLow->incident_number);
    }

    public function test_it_filters_closed_incidents_as_none_priority(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $now = now();

        $closed = $this->createIncident([
            'incident_number' => 'INC-PRIORITY-NONE',
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'opened_at' => $now->copy()->subHours(2),
            'closed_at' => $now->copy()->subHour(),
        ]);

        $active = $this->createIncident([
            'incident_number' => 'INC-ACTIVE-P1',
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'opened_at' => $now->copy()->subMinutes(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'priority' => 'NONE',
            ]));

        $response
            ->assertOk()
            ->assertSee($closed->incident_number)
            ->assertDontSee($active->incident_number);
    }

    public function test_it_rejects_invalid_priority_filter(): void
    {
        [$user] = $this->createUserWithCurrentTeam();

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'priority' => 'URGENT',
            ]));

        $response
            ->assertSessionHasErrors('priority');
    }

    public function test_it_filters_breached_response_sla(): void
    {
        $user = User::factory()->create();

        $breached = $this->createIncident([
            'incident_number' => 'INC-SLA-BREACHED-0001',
            'title' => 'Breached SLA incident',
            'severity' => 'LOW',
            'status' => 'OPEN',
            'opened_at' => now()->subDays(2),
        ]);

        $onTrack = $this->createIncident([
            'incident_number' => 'INC-SLA-TRACK-0001',
            'title' => 'On track SLA incident',
            'severity' => 'LOW',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route(
                'security-incidents.index',
                ['sla' => 'BREACHED']
            ));

        $response->assertOk();

        $response
            ->assertSee($breached->incident_number)
            ->assertDontSee($onTrack->incident_number);
    }

    public function test_it_filters_due_soon_response_sla(): void
    {
        $user = User::factory()->create();

        $dueSoon = $this->createIncident([
            'incident_number' => 'INC-SLA-DUE-0001',
            'title' => 'Due soon SLA incident',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(50),
        ]);

        $onTrack = $this->createIncident([
            'incident_number' => 'INC-SLA-TRACK-0002',
            'title' => 'On track SLA incident',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => now()->subMinutes(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route(
                'security-incidents.index',
                ['sla' => 'DUE_SOON']
            ));

        $response->assertOk();

        $response
            ->assertSee($dueSoon->incident_number)
            ->assertDontSee($onTrack->incident_number);
    }

    public function test_it_rejects_invalid_sla_filter(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'security-incidents.index',
                ['sla' => 'INVALID']
            ));

        $response->assertSessionHasErrors('sla');
    }
}
