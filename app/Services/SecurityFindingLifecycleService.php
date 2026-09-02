<?php

namespace App\Services;

use App\Models\SecurityFinding;
use App\Models\SecurityFindingHistory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SecurityFindingLifecycleService
{
    public function resolve(SecurityFinding $finding, int $actorId): SecurityFinding
    {
        return $this->transition(
            $finding,
            'RESOLVED',
            'RESOLVE',
            'Security finding ditandai sebagai resolved.',
            $actorId,
        );
    }

    public function ignore(SecurityFinding $finding, int $actorId): SecurityFinding
    {
        return $this->transition(
            $finding,
            'IGNORED',
            'IGNORE',
            'Security finding diabaikan.',
            $actorId,
        );
    }

    public function reopen(SecurityFinding $finding, int $actorId): SecurityFinding
    {
        return $this->transition(
            $finding,
            'OPEN',
            'REOPEN',
            'Security finding dibuka kembali.',
            $actorId,
        );
    }

    private function transition(
        SecurityFinding $finding,
        string $newStatus,
        string $action,
        string $notes,
        int $actorId,
    ): SecurityFinding {
        return DB::transaction(function () use ($finding, $newStatus, $action, $notes, $actorId): SecurityFinding {
            $lockedFinding = SecurityFinding::query()
                ->lockForUpdate()
                ->findOrFail($finding->id);

            $oldStatus = strtoupper((string) $lockedFinding->status);

            if ($oldStatus === $newStatus) {
                return $lockedFinding;
            }

            if (! in_array($newStatus, ['OPEN', 'RESOLVED', 'IGNORED'], true)) {
                throw new RuntimeException('Status security finding tidak valid.');
            }

            $lockedFinding->update([
                'status' => $newStatus,
                'resolved_at' => $newStatus === 'RESOLVED' ? now() : null,
            ]);

            SecurityFindingHistory::query()->create([
                'security_finding_id' => $lockedFinding->id,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'user_id' => $actorId,
            ]);

            return $lockedFinding->refresh();
        });
    }
}
