<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class SecurityAlertLifecycleService
{
    public const Open = 'OPEN';
    public const Acknowledged = 'ACKNOWLEDGED';
    public const Investigating = 'INVESTIGATING';
    public const Resolved = 'RESOLVED';

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        self::Open => [self::Acknowledged, self::Investigating, self::Resolved],
        self::Acknowledged => [self::Investigating, self::Resolved],
        self::Investigating => [self::Resolved],
        self::Resolved => [self::Open],
    ];

    public function acknowledge(SecurityAlert $alert, ?int $userId = null, ?CarbonInterface $transitionedAt = null): SecurityAlert
    {
        $transitionedAt ??= now();

        return $this->transition($alert, self::Acknowledged, 'ACKNOWLEDGE', 'Security alert di-acknowledge.', $userId, [
            'acknowledged_at' => $transitionedAt,
        ]);
    }

    public function investigate(SecurityAlert $alert, ?string $notes = null, ?int $userId = null, ?CarbonInterface $transitionedAt = null): SecurityAlert
    {
        $transitionedAt ??= now();

        return $this->transition($alert, self::Investigating, 'START_INVESTIGATION', $notes ?? 'Investigation started.', $userId, [
            'acknowledged_at' => $alert->acknowledged_at ?? $transitionedAt,
        ]);
    }

    public function resolve(SecurityAlert $alert, string $resolutionNote, ?int $userId = null, ?CarbonInterface $transitionedAt = null): SecurityAlert
    {
        $transitionedAt ??= now();

        return $this->transition($alert, self::Resolved, 'RESOLVE', $resolutionNote, $userId, [
            'acknowledged_at' => $alert->acknowledged_at ?? $transitionedAt,
            'resolved_at' => $transitionedAt,
            'resolution_note' => $resolutionNote,
        ]);
    }

    public function reopen(SecurityAlert $alert, ?int $userId = null, ?CarbonInterface $transitionedAt = null): SecurityAlert
    {
        $transitionedAt ??= now();

        return $this->transition($alert, self::Open, 'REOPEN', 'Security alert dibuka kembali.', $userId, $this->reopenAttributes($transitionedAt));
    }

    public function autoReopen(SecurityAlert $alert, int $assessmentId, CarbonInterface $occurredAt): SecurityAlert
    {
        return $this->transition($alert, self::Open, 'AUTO_REOPEN', 'Finding ditemukan kembali pada assessment #'.$assessmentId.'.', null, $this->reopenAttributes($occurredAt));
    }

    /** @return array<string, CarbonInterface|null> */
    private function reopenAttributes(CarbonInterface $slaStartedAt): array
    {
        return [
            'acknowledged_at' => null,
            'resolved_at' => null,
            'resolution_note' => null,
            'sla_started_at' => $slaStartedAt,
        ];
    }

    /** @param array<string, mixed> $attributes */
    private function transition(SecurityAlert $alert, string $newStatus, string $action, ?string $notes, ?int $userId, array $attributes): SecurityAlert
    {
        return DB::transaction(function () use ($action, $alert, $attributes, $newStatus, $notes, $userId): SecurityAlert {
            $current = SecurityAlert::query()->whereKey($alert->getKey())->lockForUpdate()->firstOrFail();

            if ($current->canonical_alert_id !== null) {
                throw new DomainException('Historical duplicate alert tidak dapat menjalani lifecycle transition.');
            }

            $oldStatus = strtoupper((string) $current->status);

            if (! in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? [], true)) {
                throw new DomainException("Invalid security alert transition: {$oldStatus} -> {$newStatus}.");
            }

            if ($newStatus !== self::Open && array_key_exists('acknowledged_at', $attributes)) {
                $attributes['acknowledged_at'] = $current->acknowledged_at
                    ?? $attributes['acknowledged_at'];
            }

            $current->update([...$attributes, 'status' => $newStatus]);

            SecurityAlertHistory::query()->create([
                'security_alert_id' => $current->id,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            return $current;
        });
    }
}
