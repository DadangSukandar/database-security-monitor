<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseUser extends Model
{
    protected $fillable = [

        'database_connection_id',

        'username',

        'host',

        'authentication_plugin',

        'can_login',

        'is_superuser',

        'is_locked',

        'can_create_database',

        'can_create_role',

        'can_grant',

        'is_replication',

        'bypass_rls',

        'risk_level',

        'risk_reasons',

        'last_scanned_at',
    ];


    protected $casts = [

        'can_login' =>
            'boolean',

        'is_superuser' =>
            'boolean',

        'is_locked' =>
            'boolean',

        'can_create_database' =>
            'boolean',

        'can_create_role' =>
            'boolean',

        'can_grant' =>
            'boolean',

        'is_replication' =>
            'boolean',

        'bypass_rls' =>
            'boolean',

        'last_scanned_at' =>
            'datetime',
    ];


    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class
        );
    }
}