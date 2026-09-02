<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentInvestigationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $creator = User::factory()->create();

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Investigation controller source alert',
            'description' => 'Source alert.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' => 'INC-'.now()->format('Ymd').'-9999',

                'security_alert_id' => $alert->id,
                'title' => 'Investigation controller incident',
                'description' => 'Incident investigation test.',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'created_by_user_id' => $creator->id,
                'opened_at' => now()->subHours(3),
                'acknowledged_at' => now()->subHours(2),
                'investigation_started_at' => now()->subHour(),
            ], $attributes)
        );
    }

    public function test_authenticated_user_can_add_investigation_note(): void
    {
        $actor = User::factory()->create();
        $incident = $this->createIncident();

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => 'Database privileges have been reviewed.',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'INVESTIGATION_NOTE',
                'notes' => 'Database privileges have been reviewed.',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_authenticated_actor_is_recorded(): void
    {
        $actor = User::factory()->create();
        $incident = $this->createIncident();

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => 'Analyst verification completed.',
                ]
            )
            ->assertSessionHasNoErrors();

        $history = SecurityIncidentHistory::query()
            ->where(
                'security_incident_id',
                $incident->id
            )
            ->where('action', 'INVESTIGATION_NOTE')
            ->firstOrFail();

        $this->assertSame(
            $actor->id,
            $history->user_id
        );
    }

    public function test_investigation_note_does_not_change_incident_status(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
            'contained_at' => now(),
        ]);

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => 'Containment verification completed.',
                ]
            )
            ->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'CONTAINED',
            $incident->status
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'INVESTIGATION_NOTE',
                'old_status' => 'CONTAINED',
                'new_status' => 'CONTAINED',
            ]
        );
    }

    public function test_investigation_note_is_required(): void
    {
        $actor = User::factory()->create();
        $incident = $this->createIncident();

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => '',
                ]
            )
            ->assertSessionHasErrors('note');

        $this->assertSame(
            0,
            SecurityIncidentHistory::query()
                ->where(
                    'security_incident_id',
                    $incident->id
                )
                ->where(
                    'action',
                    'INVESTIGATION_NOTE'
                )
                ->count()
        );
    }

    public function test_investigation_note_has_maximum_length(): void
    {
        $actor = User::factory()->create();
        $incident = $this->createIncident();

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => str_repeat('A', 5001),
                ]
            )
            ->assertSessionHasErrors('note');

        $this->assertSame(
            0,
            SecurityIncidentHistory::query()
                ->where(
                    'security_incident_id',
                    $incident->id
                )
                ->where(
                    'action',
                    'INVESTIGATION_NOTE'
                )
                ->count()
        );
    }

    public function test_note_can_be_added_to_closed_incident(): void
    {
        $actor = User::factory()->create();

        $closedAt = now()->subHour()->startOfSecond();

        $incident = $this->createIncident([
            'status' => 'CLOSED',
            'contained_at' => now()->subHours(3),
            'resolved_at' => now()->subHours(2),
            'closed_at' => $closedAt,
            'resolution_note' => 'Incident remediated.',
        ]);

        $this->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ),
                [
                    'note' => 'Post-incident verification completed.',
                ]
            )
            ->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'CLOSED',
            $incident->status
        );

        $this->assertTrue(
            $incident->closed_at->equalTo($closedAt)
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'INVESTIGATION_NOTE',
                'old_status' => 'CLOSED',
                'new_status' => 'CLOSED',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_guest_cannot_add_investigation_note(): void
    {
        $incident = $this->createIncident();

        $this->post(
            route(
                'security-incidents.investigation-notes.store',
                $incident
            ),
            [
                'note' => 'Unauthorized investigation note.',
            ]
        )->assertRedirect();

        $this->assertSame(
            0,
            SecurityIncidentHistory::query()
                ->where(
                    'security_incident_id',
                    $incident->id
                )
                ->where(
                    'action',
                    'INVESTIGATION_NOTE'
                )
                ->count()
        );
    }
}
