<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityFinding extends Model
{
    protected $fillable = [
        'database_connection_id',
        'database_name',
        'finding_type',
        'category',
        'severity',
        'title',
        'description',
        'object_type',
        'object_name',
        'username',
        'recommendation',
        'status',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /** @return BelongsTo<DatabaseConnection, $this> */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

    /** @return HasMany<SecurityFindingHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(
            SecurityFindingHistory::class,
            'security_finding_id'
        )->latest();
    }
}
