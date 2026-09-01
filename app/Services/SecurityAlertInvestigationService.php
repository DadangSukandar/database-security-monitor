<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SecurityAlertInvestigationService
{
    public function addNote(
        SecurityAlert $alert,
        string $note,
        int $userId
    ): SecurityAlertHistory {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException(
                'Investigation note cannot be empty.'
            );
        }

        return DB::transaction(function () use ($alert, $note, $userId) {
            $lockedAlert = SecurityAlert::query()
                ->lockForUpdate()
                ->findOrFail($alert->getKey());

            if ($lockedAlert->canonical_alert_id !== null) {
                throw new InvalidArgumentException(
                    'Investigation notes cannot be added to a historical duplicate alert.'
                );
            }

            return SecurityAlertHistory::create([
                'security_alert_id' => $lockedAlert->id,
                'action' => 'INVESTIGATION_NOTE',
                'old_status' => $lockedAlert->status,
                'new_status' => $lockedAlert->status,
                'notes' => $note,
                'user_id' => $userId,
            ]);
        });
    }
}
