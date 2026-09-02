<?php

namespace App\Models;

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
