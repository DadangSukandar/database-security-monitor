<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveredColumn extends Model
{
    protected $fillable = [
        'discovered_table_id',
        'name',
        'data_type',
        'column_type',
        'is_nullable',
        'default_value',
        'is_primary',
    ];

    protected $casts = [
        'is_nullable' => 'boolean',
        'is_primary' => 'boolean',
    ];

    /** @return BelongsTo<DiscoveredTable, $this> */
    public function table(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveredTable::class,
            'discovered_table_id'
        );
    }

    /** @return HasMany<SensitiveDataFinding, $this> */
    public function findings(): HasMany
    {
        return $this->hasMany(
            SensitiveDataFinding::class,
            'discovered_column_id'
        );
    }
}
