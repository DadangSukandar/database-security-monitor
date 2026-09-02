<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncidentHistory extends Model
{
    protected $fillable = [
        'security_incident_id',
        'action',
        'old_status',
        'new_status',
        'notes',
        'user_id',
    ];

    public function activityLabel(): string
    {
        return match (strtoupper((string) $this->action)) {
            'ACKNOWLEDGE' => 'Incident Acknowledged',
            'INVESTIGATE' => 'Investigation Started',
            'CONTAIN' => 'Incident Contained',
            'RESOLVE' => 'Incident Resolved',
            'CLOSE' => 'Incident Closed',

            'ASSIGN' => 'Incident Assigned',
            'REASSIGN' => 'Incident Reassigned',
            'UNASSIGN' => 'Incident Unassigned',

            'INVESTIGATION_NOTE' => 'Investigation Note',

            default => str_replace(
                '_',
                ' ',
                ucwords(
                    strtolower(
                        (string) $this->action
                    ),
                    '_'
                )
            ),
        };
    }

    public function activityCategory(): string
    {
        return match (strtoupper((string) $this->action)) {
            'ACKNOWLEDGE',
            'INVESTIGATE',
            'CONTAIN',
            'RESOLVE',
            'CLOSE' => 'LIFECYCLE',

            'ASSIGN',
            'REASSIGN',
            'UNASSIGN' => 'OWNERSHIP',

            'INVESTIGATION_NOTE' => 'INVESTIGATION',

            default => 'ACTIVITY',
        };
    }

    public function isStatusTransition(): bool
    {
        if ($this->old_status === null || $this->new_status === null) {
            return false;
        }

        return strtoupper((string) $this->old_status)
            !== strtoupper((string) $this->new_status);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(
            SecurityIncident::class,
            'security_incident_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
