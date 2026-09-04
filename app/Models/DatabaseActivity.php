<?php

namespace App\Models;

use App\Enums\DatabaseActivityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseActivity extends Model
{
    protected $fillable = [
        'database_connection_id',
        'database_name',
        'schema_name',
        'table_name',
        'username',
        'client_ip',
        'action',
        'query',
        'status',
        'error_message',
        'execution_time_ms',
        'executed_at',
    ];

    protected $casts = [
        'status' => DatabaseActivityStatus::class,
        'executed_at' => 'datetime',
        'execution_time_ms' => 'integer',
    ];

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(
            DatabaseConnection::class
        );
    }
}
