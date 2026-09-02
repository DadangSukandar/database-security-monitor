<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SecurityIncidentAssignmentService
{
    public function assign(
        SecurityIncident $incident,
        User $assignee,
        int $actorUserId
    ): SecurityIncident {
        return DB::transaction(function () use (
            $incident,
            $assignee,
            $actorUserId
        ) {
            $lockedIncident = SecurityIncident::query()
                ->lockForUpdate()
                ->findOrFail($incident->getKey());

            if (
                $lockedIncident->assigned_to_user_id ===
                $assignee->id
            ) {
                throw new InvalidArgumentException(
                    'Incident is already assigned to this user.'
                );
            }

            $previousAssigneeId =
                $lockedIncident->assigned_to_user_id;

            $action = $previousAssigneeId === null
                ? 'ASSIGN'
                : 'REASSIGN';

            $previousAssignee = $previousAssigneeId
                ? User::query()->find($previousAssigneeId)
                : null;

            $lockedIncident->update([
                'assigned_to_user_id' => $assignee->id,
                'assigned_at' => now(),
            ]);

            SecurityIncidentHistory::query()->create([
                'security_incident_id' => $lockedIncident->id,

                'action' => $action,

                'old_status' => $lockedIncident->status,

                'new_status' => $lockedIncident->status,

                'notes' => $action === 'ASSIGN'
                    ? sprintf(
                        'Incident assigned to %s.',
                        $assignee->name
                    )
                    : sprintf(
                        'Incident reassigned from %s to %s.',
                        $previousAssignee?->name
                            ?? 'Unknown User',
                        $assignee->name
                    ),

                'user_id' => $actorUserId,
            ]);

            return $lockedIncident->refresh();
        });
    }

    public function unassign(
        SecurityIncident $incident,
        int $actorUserId
    ): SecurityIncident {
        return DB::transaction(function () use (
            $incident,
            $actorUserId
        ) {
            $lockedIncident = SecurityIncident::query()
                ->lockForUpdate()
                ->findOrFail($incident->getKey());

            if (
                $lockedIncident->assigned_to_user_id ===
                null
            ) {
                throw new InvalidArgumentException(
                    'Incident is not currently assigned.'
                );
            }

            $previousAssignee = User::query()->find(
                $lockedIncident->assigned_to_user_id
            );

            $lockedIncident->update([
                'assigned_to_user_id' => null,
                'assigned_at' => null,
            ]);

            SecurityIncidentHistory::query()->create([
                'security_incident_id' => $lockedIncident->id,

                'action' => 'UNASSIGN',

                'old_status' => $lockedIncident->status,

                'new_status' => $lockedIncident->status,

                'notes' => sprintf(
                    'Incident unassigned from %s.',
                    $previousAssignee?->name
                        ?? 'Unknown User'
                ),

                'user_id' => $actorUserId,
            ]);

            return $lockedIncident->refresh();
        });
    }
}
