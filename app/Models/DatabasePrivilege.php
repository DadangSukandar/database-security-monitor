<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabasePrivilege extends Model
{
    protected $fillable = [
        'database_connection_id',
        'username',
        'host',
        'database_name',
        'schema_name',
        'table_name',
        'privilege',
        'is_grantable',
        'risk_level',
        'risk_reason',
        'last_scanned_at',
    ];

    protected $casts = [
        'is_grantable' => 'boolean',
        'last_scanned_at' => 'datetime',
    ];

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class
        );
    }
}