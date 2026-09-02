<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SecurityIncidentLifecycleService
{
    private const TRANSITIONS = [
        'OPEN' => [
            'ACKNOWLEDGED',
        ],

        'ACKNOWLEDGED' => [
            'INVESTIGATING',
        ],

        'INVESTIGATING' => [
            'CONTAINED',
        ],

        'CONTAINED' => [
            'RESOLVED',
        ],

        'RESOLVED' => [
            'CLOSED',
        ],

        'CLOSED' => [],
    ];

    public function acknowledge(
        SecurityIncident $incident,
        int $userId
    ): SecurityIncident {
        return $this->transition(
            $incident,
            'ACKNOWLEDGED',
            'ACKNOWLEDGE',
            $userId
        );
    }

    public function investigate(
        SecurityIncident $incident,
        int $userId
    ): SecurityIncident {
        return $this->transition(
            $incident,
            'INVESTIGATING',
            'INVESTIGATE',
            $userId
        );
    }

    public function contain(
        SecurityIncident $incident,
        int $userId
    ): SecurityIncident {
        return $this->transition(
            $incident,
            'CONTAINED',
            'CONTAIN',
            $userId
        );
    }

    public function resolve(
        SecurityIncident $incident,
        string $resolutionNote,
        int $userId
    ): SecurityIncident {
        $resolutionNote = trim($resolutionNote);

        if ($resolutionNote === '') {
            throw new InvalidArgumentException(
                'Resolution note cannot be empty.'
            );
        }

        return $this->transition(
            $incident,
            'RESOLVED',
            'RESOLVE',
            $userId,
            $resolutionNote
        );
    }

    public function close(
        SecurityIncident $incident,
        int $userId
    ): SecurityIncident {
        return $this->transition(
            $incident,
            'CLOSED',
            'CLOSE',
            $userId
        );
    }

    private function transition(
        SecurityIncident $incident,
        string $newStatus,
        string $action,
        int $userId,
        ?string $notes = null
    ): SecurityIncident {
        return DB::transaction(function () use (
            $incident,
            $newStatus,
            $action,
            $userId,
            $notes
        ) {
            $lockedIncident = SecurityIncident::query()
                ->lockForUpdate()
                ->findOrFail($incident->getKey());

            $oldStatus = strtoupper(
                (string) $lockedIncident->status
            );

            if (
                ! in_array(
                    $newStatus,
                    self::TRANSITIONS[$oldStatus] ?? [],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid incident transition from %s to %s.',
                        $oldStatus,
                        $newStatus
                    )
                );
            }

            $updates = [
                'status' => $newStatus,
            ];

            match ($newStatus) {
                'ACKNOWLEDGED' => $updates['acknowledged_at'] = now(),

                'INVESTIGATING' => $updates['investigation_started_at'] = now(),

                'CONTAINED' => $updates['contained_at'] = now(),

                'RESOLVED' => [
                    $updates['resolved_at'] = now(),
                    $updates['resolution_note'] = $notes,
                ],

                'CLOSED' => $updates['closed_at'] = now(),

                default => null,
            };

            $lockedIncident->update($updates);

            SecurityIncidentHistory::query()->create([
                'security_incident_id' => $lockedIncident->id,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return $lockedIncident->refresh();
        });
    }
}
