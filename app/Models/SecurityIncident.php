<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWhereResponseSlaStatus(
        Builder $query,
        string $status
    ): Builder {
        [$expression, $bindings] = $this->responseSlaStatusSql(
            $query
        );

        $status = strtoupper($status);

        if (
            ! in_array(
                $status,
                [
                    'BREACHED',
                    'DUE_SOON',
                    'ON_TRACK',
                    'MET',
                    'UNKNOWN',
                ],
                true
            )
        ) {
            return $query;
        }

        return $query->whereRaw(
            "{$expression} = ?",
            [
                ...$bindings,
                $status,
            ]
        );
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function responseSlaStatusSql(
        Builder $query
    ): array {
        $slaMinutes = [
            'CRITICAL' => (int) config(
                'security.incident_response_sla_minutes.CRITICAL',
                15
            ),
            'HIGH' => (int) config(
                'security.incident_response_sla_minutes.HIGH',
                60
            ),
            'MEDIUM' => (int) config(
                'security.incident_response_sla_minutes.MEDIUM',
                240
            ),
            'LOW' => (int) config(
                'security.incident_response_sla_minutes.LOW',
                1440
            ),
        ];

        $warningStartsAfter = [
            'CRITICAL' => $slaMinutes['CRITICAL']
                - max(
                    1,
                    (int) ceil(
                        $slaMinutes['CRITICAL'] * 0.25
                    )
                ),

            'HIGH' => $slaMinutes['HIGH']
                - max(
                    1,
                    (int) ceil(
                        $slaMinutes['HIGH'] * 0.25
                    )
                ),

            'MEDIUM' => $slaMinutes['MEDIUM']
                - max(
                    1,
                    (int) ceil(
                        $slaMinutes['MEDIUM'] * 0.25
                    )
                ),

            'LOW' => $slaMinutes['LOW']
                - max(
                    1,
                    (int) ceil(
                        $slaMinutes['LOW'] * 0.25
                    )
                ),
        ];

        $driver = $query
            ->getConnection()
            ->getDriverName();

        $lateAcknowledgement = $this->lateAcknowledgementSql(
            $driver
        );

        $severity = "UPPER(COALESCE(severity, ''))";

        $lowOrUnknown = "{$severity} NOT IN ".
            "('CRITICAL', 'HIGH', 'MEDIUM')";

        return [
            <<<SQL
            CASE
                WHEN opened_at IS NULL
                    THEN 'UNKNOWN'

                WHEN acknowledged_at IS NOT NULL
                    AND NOT (
                        ({$severity} = 'CRITICAL' AND {$lateAcknowledgement})
                        OR ({$severity} = 'HIGH' AND {$lateAcknowledgement})
                        OR ({$severity} = 'MEDIUM' AND {$lateAcknowledgement})
                        OR ({$lowOrUnknown} AND {$lateAcknowledgement})
                    )
                    THEN 'MET'

                WHEN acknowledged_at IS NOT NULL
                    THEN 'BREACHED'

                WHEN UPPER(COALESCE(status, '')) = 'CLOSED'
                    THEN 'BREACHED'

                WHEN (
                    ({$severity} = 'CRITICAL' AND opened_at < ?)
                    OR ({$severity} = 'HIGH' AND opened_at < ?)
                    OR ({$severity} = 'MEDIUM' AND opened_at < ?)
                    OR ({$lowOrUnknown} AND opened_at < ?)
                )
                    THEN 'BREACHED'

                WHEN (
                    ({$severity} = 'CRITICAL' AND opened_at <= ?)
                    OR ({$severity} = 'HIGH' AND opened_at <= ?)
                    OR ({$severity} = 'MEDIUM' AND opened_at <= ?)
                    OR ({$lowOrUnknown} AND opened_at <= ?)
                )
                    THEN 'DUE_SOON'

                ELSE 'ON_TRACK'
            END
            SQL,
            [
                $slaMinutes['CRITICAL'],
                $slaMinutes['HIGH'],
                $slaMinutes['MEDIUM'],
                $slaMinutes['LOW'],

                now()->subMinutes(
                    $slaMinutes['CRITICAL']
                ),
                now()->subMinutes(
                    $slaMinutes['HIGH']
                ),
                now()->subMinutes(
                    $slaMinutes['MEDIUM']
                ),
                now()->subMinutes(
                    $slaMinutes['LOW']
                ),

                now()->subMinutes(
                    $warningStartsAfter['CRITICAL']
                ),
                now()->subMinutes(
                    $warningStartsAfter['HIGH']
                ),
                now()->subMinutes(
                    $warningStartsAfter['MEDIUM']
                ),
                now()->subMinutes(
                    $warningStartsAfter['LOW']
                ),
            ],
        ];
    }

    public function scopeOrderByTriagePriority(
        Builder $query
    ): Builder {
        [$expression, $bindings] = $this->triagePrioritySql($query);

        return $query
            ->orderByRaw(
                "{$expression} ASC",
                $bindings
            )
            ->orderBy('opened_at')
            ->orderBy('id');
    }

    public function scopeWhereTriagePriority(
        Builder $query,
        string $priority
    ): Builder {
        [$expression, $bindings] = $this->triagePrioritySql($query);

        $rank = match (strtoupper($priority)) {
            'P1' => 1,
            'P2' => 2,
            'P3' => 3,
            'P4' => 4,
            'NONE' => 5,
            default => null,
        };

        if ($rank === null) {
            return $query;
        }

        return $query->whereRaw(
            "{$expression} = ?",
            [
                ...$bindings,
                $rank,
            ]
        );
    }

    /** @return array{0: string, 1: list<mixed>} */
    private function triagePrioritySql(Builder $query): array
    {
        $slaMinutes = [
            'CRITICAL' => (int) config('security.incident_response_sla_minutes.CRITICAL', 15),
            'HIGH' => (int) config('security.incident_response_sla_minutes.HIGH', 60),
            'MEDIUM' => (int) config('security.incident_response_sla_minutes.MEDIUM', 240),
            'LOW' => (int) config('security.incident_response_sla_minutes.LOW', 1440),
        ];

        $warningStartsAfter = [
            'MEDIUM' => $slaMinutes['MEDIUM'] - max(1, (int) ceil($slaMinutes['MEDIUM'] * 0.25)),
            'LOW' => $slaMinutes['LOW'] - max(1, (int) ceil($slaMinutes['LOW'] * 0.25)),
        ];

        $driver = $query->getConnection()->getDriverName();
        $lateAcknowledgement = $this->lateAcknowledgementSql($driver);
        $severity = "UPPER(COALESCE(severity, ''))";
        $lowOrUnknown = "{$severity} NOT IN ('CRITICAL', 'HIGH', 'MEDIUM')";

        return [
            <<<SQL
        CASE
            WHEN UPPER(COALESCE(status, '')) = 'CLOSED' THEN 5

            WHEN opened_at IS NOT NULL
                AND acknowledged_at IS NOT NULL
                AND (
                    ({$severity} = 'CRITICAL' AND {$lateAcknowledgement})
                    OR ({$severity} = 'HIGH' AND {$lateAcknowledgement})
                    OR ({$severity} = 'MEDIUM' AND {$lateAcknowledgement})
                    OR ({$lowOrUnknown} AND {$lateAcknowledgement})
                )
                THEN 1

            WHEN opened_at IS NOT NULL
                AND acknowledged_at IS NULL
                AND (
                    ({$severity} = 'CRITICAL' AND opened_at < ?)
                    OR ({$severity} = 'HIGH' AND opened_at < ?)
                    OR ({$severity} = 'MEDIUM' AND opened_at < ?)
                    OR ({$lowOrUnknown} AND opened_at < ?)
                )
                THEN 1

            WHEN {$severity} = 'CRITICAL' THEN 1

            WHEN opened_at IS NOT NULL
                AND acknowledged_at IS NULL
                AND (
                    ({$severity} = 'MEDIUM' AND opened_at <= ?)
                    OR ({$lowOrUnknown} AND opened_at <= ?)
                )
                THEN 2

            WHEN {$severity} = 'HIGH' THEN 2
            WHEN {$severity} = 'MEDIUM' THEN 3
            ELSE 4
        END
        SQL,
            [
                $slaMinutes['CRITICAL'],
                $slaMinutes['HIGH'],
                $slaMinutes['MEDIUM'],
                $slaMinutes['LOW'],
                now()->subMinutes($slaMinutes['CRITICAL']),
                now()->subMinutes($slaMinutes['HIGH']),
                now()->subMinutes($slaMinutes['MEDIUM']),
                now()->subMinutes($slaMinutes['LOW']),
                now()->subMinutes($warningStartsAfter['MEDIUM']),
                now()->subMinutes($warningStartsAfter['LOW']),
            ],
        ];
    }

    private function lateAcknowledgementSql(string $driver): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => 'acknowledged_at > DATE_ADD(opened_at, INTERVAL ? MINUTE)',
            'pgsql' => "acknowledged_at > opened_at + (? * INTERVAL '1 minute')",
            default => "acknowledged_at > datetime(opened_at, '+' || ? || ' minutes')",
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
