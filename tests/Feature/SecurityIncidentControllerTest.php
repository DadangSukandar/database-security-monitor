<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIncidentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAlert(array $attributes = []): SecurityAlert
    {
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

    public function test_authenticated_user_can_escalate_canonical_alert_to_incident(): void
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
                    'security-alerts.escalate-to-incident',
                    $alert
                )
            );

        $response->assertRedirect(
            route('security-alerts.show', $alert)
        );

        $incident = SecurityIncident::query()
            ->where('security_alert_id', $alert->id)
            ->first();

        $this->assertNotNull($incident);

        $response->assertSessionHas(
            'success',
            'Security alert berhasil dieskalasi menjadi incident '.
                $incident->incident_number.'.'
        );

        $this->assertSame(
            $actor->id,
            $incident->created_by_user_id
        );

        $this->assertSame(
            'OPEN',
            $incident->status
        );
    }

    public function test_escalation_records_authenticated_actor_in_alert_history(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
        ]);

        $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-alerts.escalate-to-incident',
                    $alert
                )
            )
            ->assertRedirect();

        $incident = SecurityIncident::query()
            ->where('security_alert_id', $alert->id)
            ->firstOrFail();

        $this->assertDatabaseHas(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'ESCALATE_TO_INCIDENT',
                'old_status' => 'INVESTIGATING',
                'new_status' => 'INVESTIGATING',
                'notes' => 'Escalated to security incident '.
                    $incident->incident_number.'.',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_escalation_does_not_change_alert_state(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $slaStartedAt = now()
            ->subHours(3)
            ->startOfSecond();

        $firstSeenAt = now()
            ->subDays(4)
            ->startOfSecond();

        $lastSeenAt = now()
            ->subMinutes(20)
            ->startOfSecond();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now()->subHour(),
            'occurrence_count' => 8,
            'sla_started_at' => $slaStartedAt,
            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);

        $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-alerts.escalate-to-incident',
                    $alert
                )
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
            8,
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

    public function test_it_rejects_escalating_historical_duplicate_alert(): void
    {
        $actor = User::factory()->create();

        $canonical = $this->createAlert();

        $duplicate = $this->createAlert([
            'canonical_alert_id' => $canonical->id,
        ]);

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $duplicate))
            ->post(
                route(
                    'security-alerts.escalate-to-incident',
                    $duplicate
                )
            );

        $response
            ->assertRedirect(
                route('security-alerts.show', $duplicate)
            )
            ->assertSessionHasErrors('alert');

        $this->assertDatabaseMissing(
            'security_incidents',
            [
                'security_alert_id' => $duplicate->id,
            ]
        );

        $this->assertDatabaseMissing(
            'security_alert_histories',
            [
                'security_alert_id' => $duplicate->id,
                'action' => 'ESCALATE_TO_INCIDENT',
            ]
        );
    }

    public function test_it_rejects_duplicate_escalation(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert();

        $this
            ->actingAs($actor)
            ->post(
                route(
                    'security-alerts.escalate-to-incident',
                    $alert
                )
            )
            ->assertRedirect();

        $response = $this
            ->actingAs($actor)
            ->from(route('security-alerts.show', $alert))
            ->post(
                route(
                    'security-alerts.escalate-to-incident',
                    $alert
                )
            );

        $response
            ->assertRedirect(
                route('security-alerts.show', $alert)
            )
            ->assertSessionHasErrors('alert');

        $this->assertSame(
            1,
            SecurityIncident::query()
                ->where(
                    'security_alert_id',
                    $alert->id
                )
                ->count()
        );

        $this->assertSame(
            1,
            SecurityAlertHistory::query()
                ->where(
                    'security_alert_id',
                    $alert->id
                )
                ->where(
                    'action',
                    'ESCALATE_TO_INCIDENT'
                )
                ->count()
        );
    }

    public function test_guest_cannot_escalate_alert_to_incident(): void
    {
        $alert = $this->createAlert();

        $response = $this->post(
            route(
                'security-alerts.escalate-to-incident',
                $alert
            )
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing(
            'security_incidents',
            [
                'security_alert_id' => $alert->id,
            ]
        );

        $this->assertDatabaseMissing(
            'security_alert_histories',
            [
                'security_alert_id' => $alert->id,
                'action' => 'ESCALATE_TO_INCIDENT',
            ]
        );
    }
}
