<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAlertInvestigationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAlert(array $attributes = []): SecurityAlert
    {
        return SecurityAlert::query()->create(array_merge([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Test security alert',
            'description' => 'Security alert created for investigation controller testing.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_authenticated_user_can_add_investigation_note(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
        ]);

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $alert))
            ->post(
                route(
                    'security-alerts.investigation-notes.store',
                    $alert
                ),
                [
                    'investigation_note' => 'Reviewed database privileges and suspicious activity.',
                ]
            );

        $response
            ->assertRedirect(route('security-alerts.show', $alert))
            ->assertSessionHas(
                'success',
                'Catatan investigasi berhasil ditambahkan.'
            );

        $this->assertDatabaseHas('security_alert_histories', [
            'security_alert_id' => $alert->id,
            'action' => 'INVESTIGATION_NOTE',
            'old_status' => 'INVESTIGATING',
            'new_status' => 'INVESTIGATING',
            'notes' => 'Reviewed database privileges and suspicious activity.',
            'user_id' => $actor->id,
        ]);
    }

    public function test_adding_investigation_note_does_not_change_alert_state(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $slaStartedAt = now()
            ->subHours(2)
            ->startOfSecond();

        $firstSeenAt = now()
            ->subDays(2)
            ->startOfSecond();

        $lastSeenAt = now()
            ->subMinutes(20)
            ->startOfSecond();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now()->subHour(),
            'sla_started_at' => $slaStartedAt,
            'occurrence_count' => 9,
            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);

        $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-alerts.investigation-notes.store',
                    $alert
                ),
                [
                    'investigation_note' => 'Additional investigation evidence.',
                ]
            )
            ->assertRedirect();

        $alert->refresh();

        $this->assertSame(
            'INVESTIGATING',
            $alert->status
        );

        $this->assertSame(
            $assignee->id,
            $alert->assigned_to_user_id
        );

        $this->assertSame(
            9,
            $alert->occurrence_count
        );

        $this->assertTrue(
            $alert->sla_started_at->equalTo($slaStartedAt)
        );

        $this->assertTrue(
            $alert->first_seen_at->equalTo($firstSeenAt)
        );

        $this->assertTrue(
            $alert->last_seen_at->equalTo($lastSeenAt)
        );
    }

    public function test_it_validates_required_investigation_note(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $alert))
            ->post(
                route(
                    'security-alerts.investigation-notes.store',
                    $alert
                ),
                [
                    'investigation_note' => '',
                ]
            );

        $response
            ->assertRedirect(route('security-alerts.show', $alert))
            ->assertSessionHasErrors('investigation_note');

        $this->assertDatabaseMissing(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'INVESTIGATION_NOTE',
            ]
        );
    }

    public function test_it_validates_maximum_investigation_note_length(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $alert))
            ->post(
                route(
                    'security-alerts.investigation-notes.store',
                    $alert
                ),
                [
                    'investigation_note' => str_repeat('A', 5001),
                ]
            );

        $response
            ->assertRedirect(route('security-alerts.show', $alert))
            ->assertSessionHasErrors('investigation_note');

        $this->assertSame(
            0,
            SecurityAlertHistory::query()
                ->where(
                    'security_alert_id',
                    $alert->id
                )
                ->where(
                    'action',
                    'INVESTIGATION_NOTE'
                )
                ->count()
        );
    }

    public function test_it_rejects_investigation_note_for_historical_duplicate(): void
    {
        $actor = User::factory()->create();

        $canonical = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $duplicate = $this->createAlert([
            'canonical_alert_id' => $canonical->id,
        ]);

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $duplicate))
            ->post(
                route(
                    'security-alerts.investigation-notes.store',
                    $duplicate
                ),
                [
                    'investigation_note' => 'This must not be stored.',
                ]
            );

        $response
            ->assertRedirect(route('security-alerts.show', $duplicate))
            ->assertSessionHasErrors('alert');

        $this->assertDatabaseMissing(
            'security_alert_histories',
            [
                'security_alert_id' => $duplicate->id,
                'action' => 'INVESTIGATION_NOTE',
            ]
        );
    }

    public function test_guest_cannot_add_investigation_note(): void
    {
        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $response = $this->post(
            route(
                'security-alerts.investigation-notes.store',
                $alert
            ),
            [
                'investigation_note' => 'Guest must not be able to write this.',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'INVESTIGATION_NOTE',
            ]
        );
    }
}
