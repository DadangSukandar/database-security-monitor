<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveredTable extends Model
{
    protected $fillable = [
        'discovered_database_id',
        'schema_name',
        'name',
        'type',
        'estimated_rows',
    ];

    protected $casts = [
        'estimated_rows' => 'integer',
    ];

    /** @return BelongsTo<DiscoveredDatabase, $this> */
    public function database(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveredDatabase::class,
            'discovered_database_id'
        );
    }

    /** @return HasMany<DiscoveredColumn, $this> */
    public function columns(): HasMany
    {
        return $this->hasMany(
            DiscoveredColumn::class,
            'discovered_table_id'
        );
    }
}
