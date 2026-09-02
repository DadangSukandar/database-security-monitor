<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use App\Services\SecurityIncidentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SecurityIncidentLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createIncident(
        array $attributes = []
    ): SecurityIncident {
        $user = User::factory()->create();

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

                'created_by_user_id' => $user->id,

                'opened_at' => now(),
            ], $attributes)
        );
    }

    public function test_it_acknowledges_open_incident_and_records_history(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident();

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->acknowledge(
            $incident,
            $actor->id
        );

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

    public function test_it_moves_acknowledged_incident_to_investigating(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'ACKNOWLEDGED',
            'acknowledged_at' => now(),
        ]);

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->investigate(
            $incident,
            $actor->id
        );

        $this->assertSame(
            'INVESTIGATING',
            $incident->status
        );

        $this->assertNotNull(
            $incident->investigation_started_at
        );

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

    public function test_it_moves_investigating_incident_to_contained(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'INVESTIGATING',
            'acknowledged_at' => now()->subHour(),
            'investigation_started_at' => now(),
        ]);

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->contain(
            $incident,
            $actor->id
        );

        $this->assertSame(
            'CONTAINED',
            $incident->status
        );

        $this->assertNotNull(
            $incident->contained_at
        );

        $this->assertDatabaseHas(
            'security_incident_histories',
            [
                'security_incident_id' => $incident->id,
                'action' => 'CONTAIN',
                'old_status' => 'INVESTIGATING',
                'new_status' => 'CONTAINED',
                'user_id' => $actor->id,
            ]
        );
    }

    public function test_it_resolves_contained_incident_with_resolution_note(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
            'contained_at' => now(),
        ]);

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->resolve(
            $incident,
            'Privilege exposure remediated.',
            $actor->id
        );

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

    public function test_resolution_note_is_trimmed(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
        ]);

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->resolve(
            $incident,
            '  Resolved safely.  ',
            $actor->id
        );

        $this->assertSame(
            'Resolved safely.',
            $incident->resolution_note
        );

        $history = SecurityIncidentHistory::query()
            ->where(
                'security_incident_id',
                $incident->id
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            'Resolved safely.',
            $history->notes
        );
    }

    public function test_it_rejects_empty_resolution_note(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CONTAINED',
        ]);

        try {
            app(
                SecurityIncidentLifecycleService::class
            )->resolve(
                $incident,
                '   ',
                $actor->id
            );

            $this->fail(
                'Expected empty resolution note to fail.'
            );
        } catch (InvalidArgumentException) {
            //
        }

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

    public function test_it_closes_resolved_incident(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'RESOLVED',
            'resolved_at' => now(),
            'resolution_note' => 'Resolved.',
        ]);

        $incident = app(
            SecurityIncidentLifecycleService::class
        )->close(
            $incident,
            $actor->id
        );

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

    public function test_it_rejects_invalid_transition_without_history(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'OPEN',
        ]);

        try {
            app(
                SecurityIncidentLifecycleService::class
            )->contain(
                $incident,
                $actor->id
            );

            $this->fail(
                'Expected invalid transition to fail.'
            );
        } catch (InvalidArgumentException) {
            //
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

    public function test_closed_incident_cannot_transition_again(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'CLOSED',
            'closed_at' => now(),
        ]);

        try {
            app(
                SecurityIncidentLifecycleService::class
            )->acknowledge(
                $incident,
                $actor->id
            );

            $this->fail(
                'Expected closed incident transition to fail.'
            );
        } catch (InvalidArgumentException) {
            //
        }

        $this->assertSame(
            0,
            $incident->histories()->count()
        );
    }

    public function test_it_rolls_back_incident_update_when_history_creation_fails(): void
    {
        $actor = User::factory()->create();

        $incident = $this->createIncident([
            'status' => 'OPEN',
        ]);

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
                SecurityIncidentLifecycleService::class
            )->acknowledge(
                $incident,
                $actor->id
            );

            $this->fail(
                'Expected lifecycle transaction to fail.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated incident history failure.',
                $exception->getMessage()
            );
        }

        $incident->refresh();

        $this->assertSame(
            'OPEN',
            $incident->status
        );

        $this->assertNull(
            $incident->acknowledged_at
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
