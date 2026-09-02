<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentLifecycleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $creator = User::factory()->create();

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Test alert',
            'description' => 'Test alert description',
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

                'title' => 'Test incident',

                'description' => 'Test incident description',

                'severity' => 'HIGH',

                'status' => 'OPEN',

                'created_by_user_id' => $creator->id,

                'opened_at' => now(),
            ], $attributes)
        );
    }

    public function test_authenticated_user_can_acknowledge_incident(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.acknowledge',
                    $incident
                )
            );

        $response->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'ACKNOWLEDGED',
            $incident->status
        );

        $this->assertNotNull(
            $incident->acknowledged_at
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'ACKNOWLEDGE',
                'old_status' => 'OPEN',
                'new_status' => 'ACKNOWLEDGED',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_authenticated_actor_is_recorded_for_investigation(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'ACKNOWLEDGED',
            'acknowledged_at' => now(),
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.investigate',
                    $incident
                )
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'INVESTIGATE',
                'old_status' => 'ACKNOWLEDGED',
                'new_status' => 'INVESTIGATING',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_authenticated_user_can_contain_incident(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'INVESTIGATING',
            'acknowledged_at' => now()->subHour(),
            'investigation_started_at' => now(),
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.contain',
                    $incident
                )
            );

        $response->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'CONTAINED',
            $incident->status
        );

        $this->assertNotNull(
            $incident->contained_at
        );
    }

    public function test_authenticated_user_can_resolve_incident_with_note(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
            'contained_at' => now(),
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.resolve',
                    $incident
                ),
                [
                    'resolution_note' => 'Privilege exposure remediated.',
                ]
            );

        $response->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'RESOLVED',
            $incident->status
        );

        $this->assertSame(
            'Privilege exposure remediated.',
            $incident->resolution_note
        );

        $this->assertNotNull(
            $incident->resolved_at
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'RESOLVE',
                'old_status' => 'CONTAINED',
                'new_status' => 'RESOLVED',
                'notes' => 'Privilege exposure remediated.',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_resolution_note_is_required(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
            'contained_at' => now(),
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.resolve',
                    $incident
                ),
                []
            );

        $response->assertSessionHasErrors(
            'resolution_note'
        );

        $incident->refresh();

        $this->assertSame(
            'CONTAINED',
            $incident->status
        );

        $this->assertNull(
            $incident->resolved_at
        );

        $this->assertSame(
            0,
            $incident->histories()->count()
        );
    }

    public function test_resolution_note_has_maximum_length(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
            'contained_at' => now(),
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.resolve',
                    $incident
                ),
                [
                    'resolution_note' => str_repeat('A', 5001),
                ]
            );

        $response->assertSessionHasErrors(
            'resolution_note'
        );

        $incident->refresh();

        $this->assertSame(
            'CONTAINED',
            $incident->status
        );

        $this->assertSame(
            0,
            $incident->histories()->count()
        );
    }

    public function test_authenticated_user_can_close_resolved_incident(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'RESOLVED',
            'resolved_at' => now(),
            'resolution_note' => 'Resolved.',
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.close',
                    $incident
                )
            );

        $response->assertSessionHasNoErrors();

        $incident->refresh();

        $this->assertSame(
            'CLOSED',
            $incident->status
        );

        $this->assertNotNull(
            $incident->closed_at
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'CLOSE',
                'old_status' => 'RESOLVED',
                'new_status' => 'CLOSED',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_invalid_transition_does_not_modify_incident(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'OPEN',
        ]);

        $response = $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-incidents.contain',
                    $incident
                )
            );

        $response->assertSessionHasErrors(
            'incident'
        );

        $incident->refresh();

        $this->assertSame(
            'OPEN',
            $incident->status
        );

        $this->assertNull(
            $incident->contained_at
        );

        $this->assertSame(
            0,
            $incident->histories()->count()
        );
    }

    public function test_guest_cannot_mutate_incident_lifecycle(): void
    {
        $incident = $this->createIncident();

        $routes = [
            'security-incidents.acknowledge',
            'security-incidents.investigate',
            'security-incidents.contain',
            'security-incidents.resolve',
            'security-incidents.close',
        ];

        foreach ($routes as $routeName) {
            $response = $this->post(
                route(
                    $routeName,
                    $incident
                ),
                [
                    'resolution_note' => 'Resolved.',
                ]
            );

            $response->assertRedirect();
        }

        $incident->refresh();

        $this->assertSame(
            'OPEN',
            $incident->status
        );

        $this->assertSame(
            0,
            $incident->histories()->count()
        );
    }
}
