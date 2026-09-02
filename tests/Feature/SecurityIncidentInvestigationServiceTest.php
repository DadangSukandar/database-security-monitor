<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use App\Services\SecurityIncidentInvestigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityIncidentInvestigationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityIncidentInvestigationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            SecurityIncidentInvestigationService::class
        );
    }

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $creator = User::factory()->create();

        $alert = SecurityAlert::query()->create([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Incident investigation source alert',
            'description' => 'Source alert for investigation testing.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return SecurityIncident::query()->create(
            array_merge([
                'incident_number' =>
                    'INC-'.now()->format('Ymd').'-9999',

                'security_alert_id' => $alert->id,
                'title' => 'Incident investigation test',
                'description' => 'Incident used for note testing.',
                'severity' => 'HIGH',
                'status' => 'INVESTIGATING',
                'created_by_user_id' => $creator->id,
                'opened_at' => now()->subHours(3),
                'acknowledged_at' => now()->subHours(2),
                'investigation_started_at' => now()->subHour(),
            ], $attributes)
        );
    }

    public function test_it_adds_investigation_note(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $history = $this->service->addNote(
            $incident,
            'Database privilege configuration is being reviewed.',
            $actor->id
        );

        $this->assertSame(
            'INVESTIGATION_NOTE',
            $history->action
        );

        $this->assertSame(
            'INVESTIGATING',
            $history->old_status
        );

        $this->assertSame(
            'INVESTIGATING',
            $history->new_status
        );

        $this->assertSame(
            'Database privilege configuration is being reviewed.',
            $history->notes
        );

        $this->assertSame(
            $actor->id,
            $history->user_id
        );

        $this->assertSame(
            $incident->id,
            $history->security_incident_id
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'INVESTIGATION_NOTE',
                'old_status' => 'INVESTIGATING',
                'new_status' => 'INVESTIGATING',
                'notes' =>
                    'Database privilege configuration is being reviewed.',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_adding_note_does_not_modify_incident_state(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $openedAt = now()->subHours(5)->startOfSecond();
        $acknowledgedAt = now()->subHours(4)->startOfSecond();
        $investigationStartedAt =
            now()->subHours(3)->startOfSecond();

        $assignedAt = now()->subHours(2)->startOfSecond();

        $incident = $this->createIncident([
            'status' => 'INVESTIGATING',
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => $assignedAt,
            'opened_at' => $openedAt,
            'acknowledged_at' => $acknowledgedAt,
            'investigation_started_at' =>
                $investigationStartedAt,
        ]);

        $originalUpdatedAt = $incident->updated_at;

        $this->service->addNote(
            $incident,
            'Investigation is still in progress.',
            $actor->id
        );

        $incident->refresh();

        $this->assertSame(
            'INVESTIGATING',
            $incident->status
        );

        $this->assertSame(
            $assignee->id,
            $incident->assigned_to_user_id
        );

        $this->assertTrue(
            $incident->assigned_at->equalTo($assignedAt)
        );

        $this->assertTrue(
            $incident->opened_at->equalTo($openedAt)
        );

        $this->assertTrue(
            $incident->acknowledged_at
                ->equalTo($acknowledgedAt)
        );

        $this->assertTrue(
            $incident->investigation_started_at
                ->equalTo($investigationStartedAt)
        );

        $this->assertTrue(
            $incident->updated_at
                ->equalTo($originalUpdatedAt)
        );
    }

    public function test_it_trims_investigation_note_before_storing_it(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $history = $this->service->addNote(
            $incident,
            '   Reviewing database permissions.   ',
            $actor->id
        );

        $this->assertSame(
            'Reviewing database permissions.',
            $history->notes
        );
    }

    public function test_it_rejects_empty_investigation_note(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->addNote(
            $incident,
            '   ',
            $actor->id
        );
    }

    public function test_empty_note_does_not_create_history(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        try {
            $this->service->addNote(
                $incident,
                '   ',
                $actor->id
            );

            $this->fail(
                'Empty investigation note should have been rejected.'
            );
        } catch (InvalidArgumentException) {
            //
        }

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

    public function test_note_can_be_added_without_changing_closed_incident(): void
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

        $history = $this->service->addNote(
            $incident,
            'Post-incident verification completed.',
            $actor->id
        );

        $incident->refresh();

        $this->assertSame(
            'CLOSED',
            $incident->status
        );

        $this->assertTrue(
            $incident->closed_at->equalTo($closedAt)
        );

        $this->assertSame(
            'CLOSED',
            $history->old_status
        );

        $this->assertSame(
            'CLOSED',
            $history->new_status
        );
    }

    public function test_history_insert_failure_does_not_modify_incident(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $assignedAt = now()->subHour()->startOfSecond();

        $incident = $this->createIncident([
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => $assignedAt,
        ]);

        DB::statement(<<<'SQL'
            CREATE TRIGGER fail_incident_investigation_history_insert
            BEFORE INSERT ON security_incident_histories
            BEGIN
                SELECT RAISE(ABORT, 'history insert failed');
            END;
        SQL);

        try {
            $this->service->addNote(
                $incident,
                'This insert should fail.',
                $actor->id
            );

            $this->fail(
                'Investigation note should have failed.'
            );
        } catch (\Throwable) {
            //
        }

        $incident->refresh();

        $this->assertSame(
            'INVESTIGATING',
            $incident->status
        );

        $this->assertSame(
            $assignee->id,
            $incident->assigned_to_user_id
        );

        $this->assertTrue(
            $incident->assigned_at->equalTo($assignedAt)
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