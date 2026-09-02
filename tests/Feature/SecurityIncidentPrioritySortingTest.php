<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentPrioritySortingTest extends TestCase
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
            'description' => 'Security incident for priority sorting.',
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

    private function createUserWithCurrentTeam(): User
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

        return $user;
    }

    public function test_incident_queue_orders_by_triage_priority(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        $p4 = $this->createIncident([
            'incident_number' => 'INC-P4',
            'severity' => 'LOW',
            'opened_at' => $now->copy()->subMinutes(30),
        ]);

        $p3 = $this->createIncident([
            'incident_number' => 'INC-P3',
            'severity' => 'MEDIUM',
            'opened_at' => $now->copy()->subMinutes(30),
        ]);

        $p2 = $this->createIncident([
            'incident_number' => 'INC-P2',
            'severity' => 'HIGH',
            'opened_at' => $now->copy()->subMinutes(10),
        ]);

        $p1 = $this->createIncident([
            'incident_number' => 'INC-P1',
            'severity' => 'CRITICAL',
            'opened_at' => $now->copy()->subMinutes(5),
        ]);

        $closed = $this->createIncident([
            'incident_number' => 'INC-CLOSED',
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'opened_at' => $now->copy()->subHours(2),
            'closed_at' => $now->copy()->subHour(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $p1->incident_number,
                $p2->incident_number,
                $p3->incident_number,
                $p4->incident_number,
                $closed->incident_number,
            ]);
    }

    public function test_breached_sla_is_sorted_as_p1(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        $breachedLow = $this->createIncident([
            'incident_number' => 'INC-BREACHED-LOW',
            'severity' => 'LOW',
            'opened_at' => $now->copy()->subMinutes(1441),
        ]);

        $high = $this->createIncident([
            'incident_number' => 'INC-HIGH',
            'severity' => 'HIGH',
            'opened_at' => $now->copy()->subMinutes(10),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $breachedLow->incident_number,
                $high->incident_number,
            ]);
    }

    public function test_due_soon_incident_is_sorted_as_p2(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        $dueSoonMedium = $this->createIncident([
            'incident_number' => 'INC-DUE-SOON',
            'severity' => 'MEDIUM',
            'opened_at' => $now->copy()->subMinutes(190),
        ]);

        $onTrackMedium = $this->createIncident([
            'incident_number' => 'INC-MEDIUM',
            'severity' => 'MEDIUM',
            'opened_at' => $now->copy()->subMinutes(30),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $dueSoonMedium->incident_number,
                $onTrackMedium->incident_number,
            ]);
    }

    public function test_older_incident_wins_when_priority_is_equal(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        $older = $this->createIncident([
            'incident_number' => 'INC-OLDER-P2',
            'severity' => 'HIGH',
            'opened_at' => $now->copy()->subMinutes(20),
        ]);

        $newer = $this->createIncident([
            'incident_number' => 'INC-NEWER-P2',
            'severity' => 'HIGH',
            'opened_at' => $now->copy()->subMinutes(10),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $older->incident_number,
                $newer->incident_number,
            ]);
    }

    public function test_priority_sorting_happens_before_pagination(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        foreach (range(1, 20) as $index) {
            $this->createIncident([
                'incident_number' => sprintf(
                    'INC-P4-%02d',
                    $index
                ),
                'severity' => 'LOW',
                'opened_at' => $now->copy()->subMinutes($index),
            ]);
        }

        $p1 = $this->createIncident([
            'incident_number' => 'INC-P1-PAGINATION',
            'severity' => 'CRITICAL',
            'opened_at' => $now,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index'));

        $response
            ->assertOk()
            ->assertSee($p1->incident_number);
    }

    public function test_priority_order_is_preserved_with_filters(): void
    {
        $user = $this->createUserWithCurrentTeam();

        $now = now();

        $critical = $this->createIncident([
            'incident_number' => 'INC-FILTER-P1',
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'opened_at' => $now->copy()->subMinutes(5),
        ]);

        $high = $this->createIncident([
            'incident_number' => 'INC-FILTER-P2',
            'severity' => 'HIGH',
            'status' => 'OPEN',
            'opened_at' => $now->copy()->subMinutes(10),
        ]);

        $this->createIncident([
            'incident_number' => 'INC-FILTER-CLOSED',
            'severity' => 'CRITICAL',
            'status' => 'CLOSED',
            'opened_at' => $now->copy()->subHours(2),
            'closed_at' => $now->copy()->subHour(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('security-incidents.index', [
                'status' => 'OPEN',
            ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $critical->incident_number,
                $high->incident_number,
            ])
            ->assertDontSee('INC-FILTER-CLOSED');
    }
}
