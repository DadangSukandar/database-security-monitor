<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityRisk extends Model
{
    protected $fillable = [

        'database_connection_id',

        'username',
        'host',

        'database_name',
        'schema_name',
        'table_name',
        'column_name',

        'privilege',
        'is_grantable',

        'sensitive_category',
        'sensitive_rule',

        'risk_level',
        'risk_reason',

        'is_resolved',

        'last_scanned_at',
    ];


    protected $casts = [

        'is_grantable' => 'boolean',

        'is_resolved' => 'boolean',

        'last_scanned_at' => 'datetime',
    ];


    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class
        );
    }
}