<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\SecurityIncident;
use App\Models\User;
use App\Services\SecurityIncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SecurityIncidentServiceTest extends TestCase
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

    public function test_it_creates_incident_from_canonical_alert(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $incident = app(SecurityIncidentService::class)
            ->createFromAlert(
                $alert,
                $actor->id
            );

        $this->assertSame(
            $alert->id,
            $incident->security_alert_id
        );

        $this->assertSame(
            'Unauthorized privilege activity',
            $incident->title
        );

        $this->assertSame(
            'Suspicious privilege activity detected.',
            $incident->description
        );

        $this->assertSame(
            'HIGH',
            $incident->severity
        );

        $this->assertSame(
            'OPEN',
            $incident->status
        );

        $this->assertSame(
            $actor->id,
            $incident->created_by_user_id
        );

        $this->assertNotNull(
            $incident->opened_at
        );

        $this->assertMatchesRegularExpression(
            '/^INC-\d{8}-\d{4}$/',
            $incident->incident_number
        );
    }

    public function test_it_records_escalation_in_alert_history(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
        ]);

        $incident = app(SecurityIncidentService::class)
            ->createFromAlert(
                $alert,
                $actor->id
            );

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

    public function test_escalation_does_not_modify_alert_state(): void
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
            'occurrence_count' => 12,
            'sla_started_at' => $slaStartedAt,
            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);

        app(SecurityIncidentService::class)
            ->createFromAlert(
                $alert,
                $actor->id
            );

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
            12,
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

    public function test_it_copies_current_alert_assignee_to_incident(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now()->subHour(),
        ]);

        $incident = app(SecurityIncidentService::class)
            ->createFromAlert(
                $alert,
                $actor->id
            );

        $this->assertSame(
            $assignee->id,
            $incident->assigned_to_user_id
        );

        $this->assertNotNull(
            $incident->assigned_at
        );
    }

    public function test_it_generates_sequential_incident_numbers(): void
    {
        $actor = User::factory()->create();

        $firstAlert = $this->createAlert([
            'title' => 'First alert',
        ]);

        $secondAlert = $this->createAlert([
            'title' => 'Second alert',
        ]);

        $service = app(SecurityIncidentService::class);

        $first = $service->createFromAlert(
            $firstAlert,
            $actor->id
        );

        $second = $service->createFromAlert(
            $secondAlert,
            $actor->id
        );

        $date = now()->format('Ymd');

        $this->assertSame(
            'INC-'.$date.'-0001',
            $first->incident_number
        );

        $this->assertSame(
            'INC-'.$date.'-0002',
            $second->incident_number
        );
    }

    public function test_it_rejects_historical_duplicate_alert(): void
    {
        $actor = User::factory()->create();

        $canonical = $this->createAlert();

        $duplicate = $this->createAlert([
            'canonical_alert_id' => $canonical->id,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        app(SecurityIncidentService::class)
            ->createFromAlert(
                $duplicate,
                $actor->id
            );
    }

    public function test_one_alert_cannot_create_multiple_incidents(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert();

        $service = app(SecurityIncidentService::class);

        $service->createFromAlert(
            $alert,
            $actor->id
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $service->createFromAlert(
            $alert,
            $actor->id
        );
    }

    public function test_duplicate_escalation_does_not_create_second_history(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert();

        $service = app(SecurityIncidentService::class);

        $service->createFromAlert(
            $alert,
            $actor->id
        );

        try {
            $service->createFromAlert(
                $alert,
                $actor->id
            );

            $this->fail(
                'Expected duplicate incident escalation to fail.'
            );
        } catch (InvalidArgumentException) {
            //
        }

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

        $this->assertSame(
            1,
            SecurityIncident::query()
                ->where(
                    'security_alert_id',
                    $alert->id
                )
                ->count()
        );
    }

    public function test_it_rolls_back_incident_when_history_creation_fails(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert();

        DB::listen(function ($query): void {
            if (
                str_contains(
                    strtolower($query->sql),
                    'insert into "security_alert_histories"'
                )
            ) {
                throw new RuntimeException(
                    'Simulated history failure.'
                );
            }
        });

        try {
            app(SecurityIncidentService::class)
                ->createFromAlert(
                    $alert,
                    $actor->id
                );

            $this->fail(
                'Expected escalation transaction to fail.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated history failure.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing(
            'security_incidents',
            [
                'security_alert_id' => $alert->id,
            ]
        );
    }
}
