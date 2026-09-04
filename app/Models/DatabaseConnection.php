<?php

namespace App\Models;

use App\Enums\DatabaseConnectionFailureType;
use App\Enums\DatabaseConnectionHealthStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class DatabaseConnection extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
        'schema',
        'is_active',
        'last_connected_at',
        'last_scanned_at',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',

        'last_connected_at' => 'datetime',

        'last_scanned_at' => 'datetime',

        'health_status' => DatabaseConnectionHealthStatus::class,

        'last_health_checked_at' => 'datetime',

        'last_failed_at' => 'datetime',

        'last_failure_type' => DatabaseConnectionFailureType::class,

        'consecutive_failures' => 'integer',

        'last_recovered_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function setPasswordAttribute(
        ?string $value
    ): void {
        $this->attributes['password'] =
            $value
                ? Crypt::encryptString(
                    $value
                )
                : null;
    }

    public function getDecryptedPassword(): ?string
    {
        if (! $this->password) {
            return null;
        }

        return Crypt::decryptString(
            $this->password
        );
    }

    /** @return HasMany<DiscoveredDatabase, $this> */
    public function discoveredDatabases(): HasMany
    {
        return $this->hasMany(
            DiscoveredDatabase::class,
            'database_connection_id'
        );
    }

    /** @return HasMany<DatabaseActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(
            DatabaseActivity::class,
            'database_connection_id'
        );
    }

    /** @return HasMany<DatabaseUser, $this> */
    public function databaseUsers(): HasMany
    {
        return $this->hasMany(
            DatabaseUser::class
        );
    }

    /** @return HasMany<DatabasePrivilege, $this> */
    public function databasePrivileges(): HasMany
    {
        return $this->hasMany(
            DatabasePrivilege::class
        );
    }

    /** @return HasMany<SecurityRisk, $this> */
    public function securityRisks(): HasMany
    {
        return $this->hasMany(
            SecurityRisk::class
        );
    }

    /** @return HasMany<SecurityFinding, $this> */
    public function securityFindings(): HasMany
    {
        return $this->hasMany(
            SecurityFinding::class
        );
    }

    /** @return HasMany<VulnerabilityAssessment, $this> */
    public function vulnerabilityAssessments(): HasMany
    {
        return $this->hasMany(
            VulnerabilityAssessment::class
        );
    }
}
