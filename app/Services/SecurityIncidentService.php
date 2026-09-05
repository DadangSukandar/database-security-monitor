<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\SecurityIncident;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SecurityIncidentService
{
    private const MAX_NUMBER_ATTEMPTS = 5;

    public function createFromAlert(
        SecurityAlert $alert,
        int $userId
    ): SecurityIncident {
        return DB::transaction(function () use ($alert, $userId) {
            $lockedAlert = SecurityAlert::query()
                ->lockForUpdate()
                ->findOrFail($alert->getKey());

            if ($lockedAlert->canonical_alert_id !== null) {
                throw new InvalidArgumentException(
                    'Historical duplicate alert cannot be escalated to an incident.'
                );
            }

            if (
                SecurityIncident::query()
                    ->where('security_alert_id', $lockedAlert->id)
                    ->exists()
            ) {
                throw new InvalidArgumentException(
                    'This security alert already has an incident.'
                );
            }

            $incident = $this->createIncident(
                $lockedAlert,
                $userId
            );

            SecurityAlertHistory::query()->create([
                'security_alert_id' => $lockedAlert->id,
                'action' => 'ESCALATE_TO_INCIDENT',
                'old_status' => $lockedAlert->status,
                'new_status' => $lockedAlert->status,
                'notes' => sprintf(
                    'Escalated to security incident %s.',
                    $incident->incident_number
                ),
                'user_id' => $userId,
            ]);

            return $incident;
        });
    }

    private function createIncident(
        SecurityAlert $alert,
        int $userId
    ): SecurityIncident {
        $lastException = null;

        for (
            $attempt = 1;
            $attempt <= self::MAX_NUMBER_ATTEMPTS;
            $attempt++
        ) {
            try {
                $incident = new SecurityIncident([
                    'incident_number' => $this->nextIncidentNumber(),

                    'security_alert_id' => $alert->id,

                    'title' => $alert->title,

                    'description' => $alert->description,

                    'severity' => strtoupper(
                        (string) $alert->severity
                    ),

                    'status' => 'OPEN',

                    /*
                    * Carry the current alert PIC into the incident.
                    *
                    * Assignment remains independent after creation.
                    */
                    'assigned_to_user_id' => $alert->assigned_to_user_id,

                    'assigned_at' => $alert->assigned_to_user_id !== null
                            ? now()
                            : null,

                    'created_by_user_id' => $userId,

                    'opened_at' => now(),
                ]);

                $incident->team_id = $alert->team_id;

                $incident->save();

                return $incident;
            } catch (QueryException $exception) {
                $lastException = $exception;

                /*
                 * A concurrently generated incident number may collide.
                 * Regenerate and retry.
                 *
                 * Other DB failures must not be hidden.
                 */
                if (! $this->isIncidentNumberCollision($exception)) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?? new InvalidArgumentException(
            'Unable to generate a unique incident number.'
        );
    }

    private function nextIncidentNumber(): string
    {
        $date = now()->format('Ymd');

        $prefix = 'INC-'.$date.'-';

        $latestNumber = SecurityIncident::query()
            ->where(
                'incident_number',
                'like',
                $prefix.'%'
            )
            ->orderByDesc('incident_number')
            ->value('incident_number');

        $sequence = 1;

        if ($latestNumber !== null) {
            $lastSequence = (int) substr(
                $latestNumber,
                strlen($prefix)
            );

            $sequence = $lastSequence + 1;
        }

        return sprintf(
            '%s%04d',
            $prefix,
            $sequence
        );
    }

    private function isIncidentNumberCollision(
        QueryException $exception
    ): bool {
        $message = strtolower(
            $exception->getMessage()
        );

        return str_contains(
            $message,
            'incident_number'
        ) && (
            str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
        );
    }
}
