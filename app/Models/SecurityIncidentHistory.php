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
