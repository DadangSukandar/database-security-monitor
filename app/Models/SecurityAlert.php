<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return match (strtoupper((string) $this->severity)) {
            'CRITICAL' => 15,
            'HIGH' => 60,
            'MEDIUM' => 240,
            default => 1440,
        };
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
}
