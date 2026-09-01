<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlertHistory extends Model
{
    protected $fillable = [
        'security_alert_id',
        'action',
        'old_status',
        'new_status',
        'notes',
        'user_id',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(SecurityAlert::class, 'security_alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
