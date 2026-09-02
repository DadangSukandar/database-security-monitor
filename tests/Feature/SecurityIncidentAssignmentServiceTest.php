<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use App\Services\SecurityIncidentAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SecurityIncidentAssignmentServiceTest extends TestCase
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

    public function test_it_assigns_incident_and_records_history(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $incident = $this->createIncident();

        $incident = app(
            SecurityIncidentAssignmentService::class
        )->assign(
            $incident,
            $assignee,
            $actor->id
        );

        $this->assertSame(
            $assignee->id,
            $incident->assigned_to_user_id
        );

        $this->assertNotNull(
            $incident->assigned_at
        );

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
    }

    public function test_it_reassigns_incident_and_records_history(): void
    {
        $actor = User::factory()->create();
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();

        $incident = $this->createIncident([
            'assigned_to_user_id' => $firstAssignee->id,

            'assigned_at' => now()->subHour(),
        ]);

        $incident = app(
            SecurityIncidentAssignmentService::class
        )->assign(
            $incident,
            $secondAssignee,
            $actor->id
        );

        $this->assertSame(
            $secondAssignee->id,
            $incident->assigned_to_user_id
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'REASSIGN',
                'old_status' => 'OPEN',
                'new_status' => 'OPEN',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_it_unassigns_incident_and_records_history(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $incident = $this->createIncident([
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $incident = app(
            SecurityIncidentAssignmentService::class
        )->unassign(
            $incident,
            $actor->id
        );

        $this->assertNull(
            $incident->assigned_to_user_id
        );

        $this->assertNull(
            $incident->assigned_at
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'UNASSIGN',
                'old_status' => 'OPEN',
                'new_status' => 'OPEN',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_it_rejects_assigning_same_user(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $incident = $this->createIncident([
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            SecurityIncidentAssignmentService::class
        )->assign(
            $incident,
            $assignee,
            $actor->id
        );
    }

    public function test_it_rejects_unassign_when_incident_has_no_assignee(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            SecurityIncidentAssignmentService::class
        )->unassign(
            $incident,
            $actor->id
        );
    }

    public function test_assignment_does_not_change_incident_lifecycle(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'INVESTIGATING',
            'acknowledged_at' => now()->subHours(2),
            'investigation_started_at' => now()->subHour(),
        ]);

        app(
            SecurityIncidentAssignmentService::class
        )->assign(
            $incident,
            $assignee,
            $actor->id
        );

        $incident->refresh();

        $this->assertSame(
            'INVESTIGATING',
            $incident->status
        );

        $this->assertNotNull(
            $incident->investigation_started_at
        );
    }

    public function test_it_rolls_back_assignment_when_history_creation_fails(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $incident = $this->createIncident();

        DB::listen(function ($query): void {
            if (
                str_contains(
                    strtolower($query->sql),
                    'insert into "security_incident_histories"'
                )
            ) {
                throw new RuntimeException(
                    'Simulated incident history failure.'
                );
            }
        });

        try {
            app(
                SecurityIncidentAssignmentService::class
            )->assign(
                $incident,
                $assignee,
                $actor->id
            );

            $this->fail(
                'Expected assignment transaction to fail.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated incident history failure.',
                $exception->getMessage()
            );
        }

        $incident->refresh();

        $this->assertNull(
            $incident->assigned_to_user_id
        );

        $this->assertNull(
            $incident->assigned_at
        );

        $this->assertSame(
            0,
            SecurityIncidentHistory::query()
                ->where(
                    'security_incident_id',
                    $incident->id
                )
                ->count()
        );
    }
}
