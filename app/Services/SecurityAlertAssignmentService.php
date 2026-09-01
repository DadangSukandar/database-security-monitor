<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class SecurityAlertAssignmentService
{
    public function assign(
        SecurityAlert $alert,
        User $assignee,
        ?int $actorUserId = null
    ): SecurityAlert {
        return DB::transaction(function () use (
            $alert,
            $assignee,
            $actorUserId
        ): SecurityAlert {
            $current = SecurityAlert::query()
                ->whereKey($alert->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureCanonical($current);

            if ($current->assigned_to_user_id === $assignee->id) {
                throw new DomainException(
                    'Security alert sudah di-assign ke user tersebut.'
                );
            }

            $previousAssigneeId = $current->assigned_to_user_id;

            $action = $previousAssigneeId === null
                ? 'ASSIGN'
                : 'REASSIGN';

            $current->update([
                'assigned_to_user_id' => $assignee->id,
                'assigned_at' => now(),
            ]);

            SecurityAlertHistory::query()->create([
                'security_alert_id' => $current->id,
                'action' => $action,
                'old_status' => $current->status,
                'new_status' => $current->status,
                'notes' => $previousAssigneeId === null
                    ? "Assigned to {$assignee->name}."
                    : "Reassigned from user #{$previousAssigneeId} to {$assignee->name}.",
                'user_id' => $actorUserId,
            ]);

            return $current->refresh();
        });
    }

    public function unassign(
        SecurityAlert $alert,
        ?int $actorUserId = null
    ): SecurityAlert {
        return DB::transaction(function () use (
            $alert,
            $actorUserId
        ): SecurityAlert {
            $current = SecurityAlert::query()
                ->whereKey($alert->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureCanonical($current);

            if ($current->assigned_to_user_id === null) {
                throw new DomainException(
                    'Security alert belum memiliki assignee.'
                );
            }

            $previousAssigneeId = $current->assigned_to_user_id;
            $previousAssigneeName =
                $current->assignedTo()->value('name')
                ?? "User #{$previousAssigneeId}";

            $current->update([
                'assigned_to_user_id' => null,
                'assigned_at' => null,
            ]);

            SecurityAlertHistory::query()->create([
                'security_alert_id' => $current->id,
                'action' => 'UNASSIGN',
                'old_status' => $current->status,
                'new_status' => $current->status,
                'notes' => "Unassigned from {$previousAssigneeName}.",
                'user_id' => $actorUserId,
            ]);

            return $current->refresh();
        });
    }

    private function ensureCanonical(SecurityAlert $alert): void
    {
        if ($alert->canonical_alert_id !== null) {
            throw new DomainException(
                'Historical duplicate alert tidak dapat di-assign.'
            );
        }
    }
}
