<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;
use App\Services\SecurityAlertInvestigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityAlertInvestigationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityAlertInvestigationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SecurityAlertInvestigationService::class);
    }

    private function createAlert(array $attributes = []): SecurityAlert
    {
        return SecurityAlert::query()->create(array_merge([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Test security alert',
            'description' => 'Security alert created for investigation testing.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_it_adds_investigation_note_to_canonical_alert(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
        ]);

        $history = $this->service->addNote(
            $alert,
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
            $alert->id,
            $history->security_alert_id
        );

        $this->assertDatabaseHas('security_alert_histories', [
            'security_alert_id' => $alert->id,
            'action' => 'INVESTIGATION_NOTE',
            'old_status' => 'INVESTIGATING',
            'new_status' => 'INVESTIGATING',
            'notes' => 'Database privilege configuration is being reviewed.',
            'user_id' => $actor->id,
        ]);
    }

    public function test_adding_note_does_not_modify_alert_state(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $slaStartedAt = now()->subHours(2)->startOfSecond();
        $firstSeenAt = now()->subDays(3)->startOfSecond();
        $lastSeenAt = now()->subMinutes(15)->startOfSecond();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now()->subHour(),
            'sla_started_at' => $slaStartedAt,
            'occurrence_count' => 7,
            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);

        $originalUpdatedAt = $alert->updated_at;

        $this->service->addNote(
            $alert,
            'Investigation is still in progress.',
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
            7,
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

        $this->assertTrue(
            $alert->updated_at->equalTo($originalUpdatedAt)
        );
    }

    public function test_it_trims_investigation_note_before_storing_it(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $history = $this->service->addNote(
            $alert,
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

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->addNote(
            $alert,
            '   ',
            $actor->id
        );
    }

    public function test_empty_note_does_not_create_history(): void
    {
        $actor = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        try {
            $this->service->addNote(
                $alert,
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
            SecurityAlertHistory::query()
                ->where(
                    'security_alert_id',
                    $alert->id
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

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->addNote(
            $duplicate,
            'This note must not be stored.',
            $actor->id
        );
    }

    public function test_historical_duplicate_rejection_does_not_create_history(): void
    {
        $actor = User::factory()->create();

        $canonical = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $duplicate = $this->createAlert([
            'canonical_alert_id' => $canonical->id,
        ]);

        try {
            $this->service->addNote(
                $duplicate,
                'This note must not be stored.',
                $actor->id
            );

            $this->fail(
                'Historical duplicate should have been rejected.'
            );
        } catch (InvalidArgumentException) {
            //
        }

        $this->assertSame(
            0,
            SecurityAlertHistory::query()
                ->where(
                    'security_alert_id',
                    $duplicate->id
                )
                ->count()
        );
    }

    public function test_history_insert_failure_does_not_modify_alert(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
            'occurrence_count' => 4,
        ]);

        DB::statement(<<<'SQL'
            CREATE TRIGGER fail_investigation_history_insert
            BEFORE INSERT ON security_alert_histories
            BEGIN
                SELECT RAISE(ABORT, 'history insert failed');
            END;
        SQL);

        try {
            $this->service->addNote(
                $alert,
                'This insert should fail.',
                $actor->id
            );

            $this->fail(
                'Investigation note should have failed.'
            );
        } catch (\Throwable) {
            //
        }

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
            4,
            $alert->occurrence_count
        );

        $this->assertSame(
            0,
            SecurityAlertHistory::query()
                ->where(
                    'security_alert_id',
                    $alert->id
                )
                ->count()
        );
    }
}
