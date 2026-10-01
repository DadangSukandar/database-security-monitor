<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPolicy extends Model
{
    protected $fillable = [
        'name',
        'code',
        'rule_type',
        'severity',
        'conditions',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(
            Team::class
        );
    }

    /**
     * @param  Builder<SecurityPolicy>  $query
     */
    public function scopeForTeam(
        Builder $query,
        int $teamId
    ): Builder {
        return $query->where(
            'team_id',
            $teamId
        );
    }
}
