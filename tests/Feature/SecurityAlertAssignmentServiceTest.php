<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;
use App\Services\SecurityAlertAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAlertAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityAlertAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SecurityAlertAssignmentService::class);
    }

    private function createAlert(array $attributes = []): SecurityAlert
    {
        return SecurityAlert::query()->create(array_merge([
            'alert_type' => 'VULNERABILITY',
            'severity' => 'HIGH',
            'title' => 'Test security alert',
            'description' => 'Security alert created for assignment testing.',
            'status' => 'OPEN',
            'detected_at' => now(),
            'sla_started_at' => now(),
            'occurrence_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }

    public function test_it_assigns_an_alert_and_records_history(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'OPEN',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        $result = $this->service->assign(
            $alert,
            $assignee,
            $actor->id
        );

        $this->assertSame(
            $assignee->id,
            $result->assigned_to_user_id
        );

        $this->assertNotNull($result->assigned_at);

        $this->assertDatabaseHas('security_alert_histories', [
            'security_alert_id' => $alert->id,
            'action' => 'ASSIGN',
            'old_status' => 'OPEN',
            'new_status' => 'OPEN',
            'user_id' => $actor->id,
        ]);
    }

    public function test_it_reassigns_an_alert_and_records_history(): void
    {
        $actor = User::factory()->create();
        $oldAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'INVESTIGATING',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $oldAssignee->id,
            'assigned_at' => now()->subHour(),
        ]);

        $result = $this->service->assign(
            $alert,
            $newAssignee,
            $actor->id
        );

        $this->assertSame(
            $newAssignee->id,
            $result->assigned_to_user_id
        );

        $this->assertDatabaseHas('security_alert_histories', [
            'security_alert_id' => $alert->id,
            'action' => 'REASSIGN',
            'old_status' => 'INVESTIGATING',
            'new_status' => 'INVESTIGATING',
            'user_id' => $actor->id,
        ]);
    }

    public function test_it_unassigns_an_alert_and_records_history(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'ACKNOWLEDGED',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $result = $this->service->unassign(
            $alert,
            $actor->id
        );

        $this->assertNull($result->assigned_to_user_id);
        $this->assertNull($result->assigned_at);

        $this->assertDatabaseHas('security_alert_histories', [
            'security_alert_id' => $alert->id,
            'action' => 'UNASSIGN',
            'old_status' => 'ACKNOWLEDGED',
            'new_status' => 'ACKNOWLEDGED',
            'user_id' => $actor->id,
        ]);
    }

    public function test_it_rejects_assigning_to_the_same_user(): void
    {
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'canonical_alert_id' => null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(\DomainException::class);

        $this->service->assign(
            $alert,
            $assignee
        );
    }

    public function test_it_rejects_unassign_when_alert_has_no_assignee(): void
    {
        $alert = $this->createAlert([
            'canonical_alert_id' => null,
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        $this->expectException(\DomainException::class);

        $this->service->unassign($alert);
    }

    public function test_it_rejects_assignment_for_historical_duplicate(): void
    {
        $canonical = $this->createAlert([
            'canonical_alert_id' => null,
        ]);

        $duplicate = $this->createAlert([
            'canonical_alert_id' => $canonical->id,
        ]);

        $assignee = User::factory()->create();

        $this->expectException(\DomainException::class);

        $this->service->assign(
            $duplicate,
            $assignee
        );
    }

    public function test_it_rolls_back_assignment_when_history_creation_fails(): void
    {
        $assignee = User::factory()->create();

        $alert = $this->createAlert([
            'status' => 'OPEN',
            'canonical_alert_id' => null,
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        DB::statement(<<<'SQL'
            CREATE TRIGGER fail_security_alert_history_insert
            BEFORE INSERT ON security_alert_histories
            BEGIN
                SELECT RAISE(ABORT, 'history insert failed');
            END;
        SQL);

        try {
            $this->service->assign(
                $alert,
                $assignee
            );

            $this->fail(
                'Assignment seharusnya gagal.'
            );
        } catch (\Throwable) {
            //
        }

        $alert->refresh();

        $this->assertNull(
            $alert->assigned_to_user_id
        );

        $this->assertNull(
            $alert->assigned_at
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
