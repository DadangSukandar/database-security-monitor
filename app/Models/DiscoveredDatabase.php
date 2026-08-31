<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveredDatabase extends Model
{
    protected $fillable = [
        'database_connection_id',
        'name',
        'engine',
        'version',
    ];

    /** @return BelongsTo<DatabaseConnection, $this> */
    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class,
            'database_connection_id'
        );
    }

    /** @return HasMany<DiscoveredTable, $this> */
    public function tables(): HasMany
    {
        return $this->hasMany(
            DiscoveredTable::class,
            'discovered_database_id'
        );
    }
}
