<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityFindingHistory extends Model
{
    protected $table = 'security_finding_histories';

    protected $fillable = [
        'security_finding_id',
        'action',
        'old_status',
        'new_status',
        'notes',
        'user_id',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(
            SecurityFinding::class,
            'security_finding_id'
        );
    }
}