<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SecurityAlert extends Model
{
    /**
     * =========================================================
     * TABLE
     * =========================================================
     */
    protected $table = 'security_alerts';

    /**
     * =========================================================
     * FILLABLE
     * =========================================================
     */
    protected $fillable = [

        'database_activity_id',

        'database_connection_id',

        'database_name',

        'username',

        'client_ip',

        'alert_type',

        'fingerprint',

        'action',

        'rule',

        'severity',

        'title',

        'description',

        'query',

        'table_name',

        'status',

        'occurrence_count',

        'first_seen_at',

        'last_seen_at',

        'last_assessment_id',

        'canonical_alert_id',

        'consolidated_at',

        'detected_at',

        'sla_started_at',

        'acknowledged_at',

        'resolved_at',

        'resolution_note',

        'assigned_to_user_id',

        'assigned_at',
    ];

    /**
     * =========================================================
     * CASTS
     * =========================================================
     */
    protected $casts = [

        'occurrence_count' => 'integer',

        'last_assessment_id' => 'integer',

        'canonical_alert_id' => 'integer',

        'consolidated_at' => 'datetime',

        'first_seen_at' => 'datetime',

        'last_seen_at' => 'datetime',

        'detected_at' => 'datetime',

        'sla_started_at' => 'datetime',

        'acknowledged_at' => 'datetime',

        'resolved_at' => 'datetime',

        'assigned_at' => 'datetime',
    ];

    /**
     * =========================================================
     * DATABASE ACTIVITY
     * =========================================================
     */
    public function databaseActivity(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseActivity::class,
            'database_activity_id'
        );
    }

    /**
     * =========================================================
     * DATABASE CONNECTION
     * =========================================================
     */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class,
            'database_connection_id'
        );
    }

    /**
     * =========================================================
     * ALIAS ACTIVITY
     *
     * Berguna jika controller/view lama menggunakan:
     *
     * $alert->activity
     * =========================================================
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseActivity::class,
            'database_activity_id'
        );
    }

    /**
     * =========================================================
     * ALIAS CONNECTION
     *
     * Berguna jika kode lama menggunakan:
     *
     * $alert->connection
     * =========================================================
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class,
            'database_connection_id'
        );
    }

    /**
     * =========================================================
     * ALERT HISTORY
     * =========================================================
     */
    public function histories(): HasMany
    {
        return $this->hasMany(
            SecurityAlertHistory::class,
            'security_alert_id'
        )->latest();
    }

    /** @param Builder<SecurityAlert> $query */
    public function scopeCanonical(Builder $query): Builder
    {
        return $query->whereNull('canonical_alert_id');
    }

    /**
     * @param  Builder<SecurityAlert>  $query
     */
    public function scopeWhereResponseSlaStatus(
        Builder $query,
        string $status,
        ?CarbonInterface $at = null
    ): Builder {
        $status = strtoupper(trim($status));
        $at ??= now();

        if (! in_array(
            $status,
            ['UNKNOWN', 'MET', 'BREACHED', 'DUE_SOON', 'ON_TRACK'],
            true
        )) {
            return $query->whereRaw('1 = 0');
        }

        $startExpression = 'COALESCE(sla_started_at, detected_at)';

        $slaMinutesExpression = $this->responseSlaMinutesSqlExpression();

        $deadlineExpression = $this->addMinutesSqlExpression(
            $startExpression,
            $slaMinutesExpression
        );

        $warningMinutesExpression = $this->responseSlaWarningMinutesSqlExpression();

        $warningExpression = $this->subtractMinutesSqlExpression(
            $deadlineExpression,
            $warningMinutesExpression
        );

        return match ($status) {
            'UNKNOWN' => $query->whereNull('sla_started_at')
                ->whereNull('detected_at'),

            'MET' => $query
                ->whereRaw("{$startExpression} IS NOT NULL")
                ->whereNotNull('acknowledged_at')
                ->whereRaw("acknowledged_at <= {$deadlineExpression}"),

            'BREACHED' => $query
                ->whereRaw("{$startExpression} IS NOT NULL")
                ->where(function (Builder $query) use (
                    $deadlineExpression,
                    $at
                ): void {
                    $query
                        ->where(function (Builder $query) use (
                            $deadlineExpression
                        ): void {
                            $query
                                ->whereNotNull('acknowledged_at')
                                ->whereRaw(
                                    "acknowledged_at > {$deadlineExpression}"
                                );
                        })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->whereNull('acknowledged_at')
                                ->where('status', 'RESOLVED');
                        })
                        ->orWhere(function (Builder $query) use (
                            $deadlineExpression,
                            $at
                        ): void {
                            $query
                                ->whereNull('acknowledged_at')
                                ->whereRaw(
                                    "? > {$deadlineExpression}",
                                    [$at]
                                );
                        });
                }),

            'DUE_SOON' => $query
                ->whereRaw("{$startExpression} IS NOT NULL")
                ->whereNull('acknowledged_at')
                ->where('status', '!=', 'RESOLVED')
                ->whereRaw("? <= {$deadlineExpression}", [$at])
                ->whereRaw("? >= {$warningExpression}", [$at]),

            'ON_TRACK' => $query
                ->whereRaw("{$startExpression} IS NOT NULL")
                ->whereNull('acknowledged_at')
                ->where('status', '!=', 'RESOLVED')
                ->whereRaw("? < {$warningExpression}", [$at]),

            default => $query->whereRaw('1 = 0'),
        };
    }

    private function responseSlaMinutesSqlExpression(): string
    {
        $critical = (int) config(
            'security.alert_response_sla_minutes.CRITICAL',
            15
        );

        $high = (int) config(
            'security.alert_response_sla_minutes.HIGH',
            60
        );

        $medium = (int) config(
            'security.alert_response_sla_minutes.MEDIUM',
            240
        );

        $low = (int) config(
            'security.alert_response_sla_minutes.LOW',
            1440
        );

        return "
            CASE UPPER(severity)
                WHEN 'CRITICAL' THEN {$critical}
                WHEN 'HIGH' THEN {$high}
                WHEN 'MEDIUM' THEN {$medium}
                ELSE {$low}
            END
        ";
    }

    private function responseSlaWarningMinutesSqlExpression(): string
    {
        $critical = (int) ceil(
            config('security.alert_response_sla_minutes.CRITICAL', 15) * 0.25
        );

        $high = (int) ceil(
            config('security.alert_response_sla_minutes.HIGH', 60) * 0.25
        );

        $medium = (int) ceil(
            config('security.alert_response_sla_minutes.MEDIUM', 240) * 0.25
        );

        $low = (int) ceil(
            config('security.alert_response_sla_minutes.LOW', 1440) * 0.25
        );

        return "
            CASE UPPER(severity)
                WHEN 'CRITICAL' THEN {$critical}
                WHEN 'HIGH' THEN {$high}
                WHEN 'MEDIUM' THEN {$medium}
                ELSE {$low}
            END
        ";
    }

    private function addMinutesSqlExpression(
        string $dateExpression,
        string $minutesExpression
    ): string {
        $driver = $this->getConnection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => "DATE_ADD({$dateExpression}, INTERVAL ({$minutesExpression}) MINUTE)",

            'pgsql' => "({$dateExpression} + ({$minutesExpression}) * INTERVAL '1 minute')",

            default => "datetime({$dateExpression}, '+' || ({$minutesExpression}) || ' minutes')",
        };
    }

    private function subtractMinutesSqlExpression(
        string $dateExpression,
        string $minutesExpression
    ): string {
        $driver = $this->getConnection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => "DATE_SUB({$dateExpression}, INTERVAL ({$minutesExpression}) MINUTE)",

            'pgsql' => "({$dateExpression} - ({$minutesExpression}) * INTERVAL '1 minute')",

            default => "datetime({$dateExpression}, '-' || ({$minutesExpression}) || ' minutes')",
        };
    }

    public function canonicalAlert(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_alert_id');
    }

    public function historicalDuplicates(): HasMany
    {
        return $this->hasMany(self::class, 'canonical_alert_id');
    }

    /**
     * =========================================================
     * HELPER - OPEN
     * =========================================================
     */
    public function isOpen(): bool
    {
        return strtoupper(
            (string) $this->status
        ) === 'OPEN';
    }

    /**
     * =========================================================
     * HELPER - RESOLVED
     * =========================================================
     */
    public function isResolved(): bool
    {
        return strtoupper(
            (string) $this->status
        ) === 'RESOLVED';
    }

    /**
     * =========================================================
     * HELPER - CRITICAL
     * =========================================================
     */
    public function isCritical(): bool
    {
        return strtoupper(
            (string) $this->severity
        ) === 'CRITICAL';
    }

    /**
     * =========================================================
     * HELPER - HIGH
     * =========================================================
     */
    public function isHigh(): bool
    {
        return strtoupper(
            (string) $this->severity
        ) === 'HIGH';
    }

    public function responseSlaMinutes(): int
    {
        $severity = strtoupper(
            (string) $this->severity
        );

        return (int) config(
            "security.alert_response_sla_minutes.{$severity}",
            config('security.alert_response_sla_minutes.LOW', 1440)
        );
    }

    public function responseSlaDeadline(): ?CarbonInterface
    {
        return $this->responseSlaStartedAt()?->copy()->addMinutes(
            $this->responseSlaMinutes()
        );
    }

    public function responseSlaStartedAt(): ?CarbonInterface
    {
        return $this->sla_started_at ?? $this->detected_at;
    }

    public function responseSlaStatus(?CarbonInterface $at = null): string
    {
        $deadline = $this->responseSlaDeadline();

        if ($deadline === null) {
            return 'UNKNOWN';
        }

        if ($this->acknowledged_at !== null) {
            return $this->acknowledged_at->lte($deadline)
                ? 'MET'
                : 'BREACHED';
        }

        if ($this->isResolved()) {
            return 'BREACHED';
        }

        $at ??= now();

        if ($at->gt($deadline)) {
            return 'BREACHED';
        }

        $warningThreshold = $deadline->copy()->subMinutes(
            (int) ceil($this->responseSlaMinutes() * 0.25)
        );

        return $at->gte($warningThreshold)
            ? 'DUE_SOON'
            : 'ON_TRACK';
    }

    public function hasBreachedResponseSla(?CarbonInterface $at = null): bool
    {
        return $this->responseSlaStatus($at) === 'BREACHED';
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to_user_id'
        );
    }

    public function incident(): HasOne
    {
        return $this->hasOne(
            SecurityIncident::class,
            'security_alert_id'
        );
    }
}
