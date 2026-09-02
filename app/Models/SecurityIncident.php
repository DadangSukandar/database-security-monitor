<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityIncident extends Model
{
    protected $fillable = [
        'incident_number',
        'security_alert_id',
        'title',
        'description',
        'severity',
        'status',
        'assigned_to_user_id',
        'assigned_at',
        'created_by_user_id',
        'opened_at',
        'acknowledged_at',
        'investigation_started_at',
        'contained_at',
        'resolved_at',
        'closed_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'opened_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'investigation_started_at' => 'datetime',
            'contained_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function ageEndedAt(
        ?CarbonInterface $at = null
    ): ?CarbonInterface {
        if ($this->opened_at === null) {
            return null;
        }

        if ($this->closed_at !== null) {
            return $this->closed_at;
        }

        return $at ?? now();
    }

    public function ageMinutes(
        ?CarbonInterface $at = null
    ): ?int {
        $endedAt = $this->ageEndedAt($at);

        if (
            $this->opened_at === null ||
            $endedAt === null
        ) {
            return null;
        }

        return (int) floor(
            $this->opened_at->diffInMinutes(
                $endedAt
            )
        );
    }

    public function ageLabel(
        ?CarbonInterface $at = null
    ): string {
        $minutes = $this->ageMinutes($at);

        if ($minutes === null) {
            return 'Unknown';
        }

        if ($minutes < 60) {
            return $minutes.'m';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            $remainingMinutes = $minutes % 60;

            return $remainingMinutes > 0
                ? $hours.'h '.$remainingMinutes.'m'
                : $hours.'h';
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? $days.'d '.$remainingHours.'h'
            : $days.'d';
    }

    public function responseSlaMinutes(): int
    {
        $severity = strtoupper(
            (string) $this->severity
        );

        return (int) config(
            "security.incident_response_sla_minutes.{$severity}",
            config('security.incident_response_sla_minutes.LOW', 1440)
        );
    }

    public function responseSlaDeadline(): ?CarbonInterface
    {
        if ($this->opened_at === null) {
            return null;
        }

        return $this->opened_at
            ->copy()
            ->addMinutes(
                $this->responseSlaMinutes()
            );
    }

    public function responseSlaStatus(
        ?CarbonInterface $at = null
    ): string {
        $deadline = $this->responseSlaDeadline();

        if ($deadline === null) {
            return 'UNKNOWN';
        }

        if ($this->acknowledged_at !== null) {
            return $this->acknowledged_at->lte($deadline)
                ? 'MET'
                : 'BREACHED';
        }

        if ($this->status === 'CLOSED') {
            return 'BREACHED';
        }

        $at ??= now();

        if ($at->gt($deadline)) {
            return 'BREACHED';
        }

        $warningThreshold = $deadline
            ->copy()
            ->subMinutes(
                (int) ceil(
                    $this->responseSlaMinutes() * 0.25
                )
            );

        return $at->gte($warningThreshold)
            ? 'DUE_SOON'
            : 'ON_TRACK';
    }

    public function hasBreachedResponseSla(
        ?CarbonInterface $at = null
    ): bool {
        return $this->responseSlaStatus($at) === 'BREACHED';
    }

    public function triagePriority(
        ?CarbonInterface $at = null
    ): string {
        if (strtoupper((string) $this->status) === 'CLOSED') {
            return 'NONE';
        }

        $severity = strtoupper(
            (string) $this->severity
        );

        $slaStatus = $this->responseSlaStatus($at);

        if (
            $slaStatus === 'BREACHED' ||
            $severity === 'CRITICAL'
        ) {
            return 'P1';
        }

        if (
            $slaStatus === 'DUE_SOON' ||
            $severity === 'HIGH'
        ) {
            return 'P2';
        }

        if ($severity === 'MEDIUM') {
            return 'P3';
        }

        return 'P4';
    }

    public function triagePriorityLabel(
        ?CarbonInterface $at = null
    ): string {
        return match ($this->triagePriority($at)) {
            'P1' => 'Immediate',
            'P2' => 'High',
            'P3' => 'Normal',
            'P4' => 'Low',
            default => 'Closed',
        };
    }

    public function securityAlert(): BelongsTo
    {
        return $this->belongsTo(
            SecurityAlert::class,
            'security_alert_id'
        );
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to_user_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function histories(): HasMany
    {
        return $this->hasMany(
            SecurityIncidentHistory::class,
            'security_incident_id'
        );
    }
}
