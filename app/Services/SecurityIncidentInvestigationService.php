<?php

namespace App\Services;

use App\Models\SecurityIncident;
use App\Models\SecurityIncidentHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SecurityIncidentInvestigationService
{
    public function addNote(
        SecurityIncident $incident,
        string $note,
        int $userId
    ): SecurityIncidentHistory {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException(
                'Investigation note cannot be empty.'
            );
        }

        return DB::transaction(function () use (
            $incident,
            $note,
            $userId
        ) {
            $lockedIncident = SecurityIncident::query()
                ->lockForUpdate()
                ->findOrFail($incident->getKey());

            return SecurityIncidentHistory::query()->create([
                'security_incident_id' => $lockedIncident->id,
                'action' => 'INVESTIGATION_NOTE',
                'old_status' => $lockedIncident->status,
                'new_status' => $lockedIncident->status,
                'notes' => $note,
                'user_id' => $userId,
            ]);
        });
    }
}