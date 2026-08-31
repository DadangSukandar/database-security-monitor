<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensitiveDataFinding extends Model
{
    protected $fillable = [
        'discovered_column_id',
        'category',
        'risk_level',
        'rule_name',
        'description',
    ];

    /** @return BelongsTo<DiscoveredColumn, $this> */
    public function column(): BelongsTo
    {
        return $this->belongsTo(
            DiscoveredColumn::class,
            'discovered_column_id'
        );
    }
}
