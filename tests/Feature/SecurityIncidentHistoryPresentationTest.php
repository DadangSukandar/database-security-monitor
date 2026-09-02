<?php

namespace Tests\Feature;

use App\Models\SecurityIncidentHistory;
use Tests\TestCase;

class SecurityIncidentHistoryPresentationTest extends TestCase
{
    public function test_lifecycle_actions_have_human_readable_labels(): void
    {
        $history = new SecurityIncidentHistory([
            'action' => 'ACKNOWLEDGE',
        ]);

        $this->assertSame(
            'Incident Acknowledged',
            $history->activityLabel()
        );

        $this->assertSame(
            'LIFECYCLE',
            $history->activityCategory()
        );
    }

    public function test_assignment_actions_are_ownership_activity(): void
    {
        $history = new SecurityIncidentHistory([
            'action' => 'REASSIGN',
        ]);

        $this->assertSame(
            'Incident Reassigned',
            $history->activityLabel()
        );

        $this->assertSame(
            'OWNERSHIP',
            $history->activityCategory()
        );
    }

    public function test_investigation_note_is_investigation_activity(): void
    {
        $history = new SecurityIncidentHistory([
            'action' => 'INVESTIGATION_NOTE',
        ]);

        $this->assertSame(
            'Investigation Note',
            $history->activityLabel()
        );

        $this->assertSame(
            'INVESTIGATION',
            $history->activityCategory()
        );
    }

    public function test_real_status_change_is_detected_as_transition(): void
    {
        $history = new SecurityIncidentHistory([
            'old_status' => 'OPEN',
            'new_status' => 'ACKNOWLEDGED',
        ]);

        $this->assertTrue(
            $history->isStatusTransition()
        );
    }

    public function test_same_status_activity_is_not_a_transition(): void
    {
        $history = new SecurityIncidentHistory([
            'old_status' => 'INVESTIGATING',
            'new_status' => 'INVESTIGATING',
        ]);

        $this->assertFalse(
            $history->isStatusTransition()
        );
    }

    public function test_missing_status_is_not_a_transition(): void
    {
        $history = new SecurityIncidentHistory([
            'old_status' => null,
            'new_status' => null,
        ]);

        $this->assertFalse(
            $history->isStatusTransition()
        );
    }

    public function test_unknown_action_has_safe_fallback_label(): void
    {
        $history = new SecurityIncidentHistory([
            'action' => 'CUSTOM_ACTIVITY',
        ]);

        $this->assertSame(
            'Custom Activity',
            $history->activityLabel()
        );

        $this->assertSame(
            'ACTIVITY',
            $history->activityCategory()
        );
    }
}
