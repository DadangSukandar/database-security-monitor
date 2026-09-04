<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Exceptions\DatabaseConnectionException;

it('does not retain the raw database exception chain', function () {
    $rawException =
        new RuntimeException(
            'SQLSTATE connection failed '.
            'host=secret-db.internal '.
            'user=guardium_monitor '.
            'password=SuperSecret123'
        );

    $exception =
        new DatabaseConnectionException(
            DatabaseConnectionFailureType::AUTHENTICATION_FAILED,
            $rawException
        );

    expect(
        $exception->getMessage()
    )
        ->toBe(
            'Database monitoring connection failed.'
        )
        ->and(
            $exception->getPrevious()
        )
        ->toBeNull();
});

it('does not expose connection secrets when stringified for logging', function () {
    $password =
        'CredentialLeakSentinel987';

    $host =
        'private-db.internal';

    $username =
        'monitoring_secret_user';

    $database =
        'private_customer_database';

    $rawException =
        new RuntimeException(
            'SQLSTATE authentication failure '.
            "host={$host} ".
            "user={$username} ".
            "database={$database} ".
            "password={$password}"
        );

    $exception =
        new DatabaseConnectionException(
            DatabaseConnectionFailureType::AUTHENTICATION_FAILED,
            $rawException
        );

    $rendered =
        (string) $exception;

    expect($rendered)
        ->not->toContain(
            $password
        )
        ->not->toContain(
            $host
        )
        ->not->toContain(
            $username
        )
        ->not->toContain(
            $database
        )
        ->not->toContain(
            'SQLSTATE authentication failure'
        );
});

it('provides only classified safe logging context', function () {
    $rawException =
        new RuntimeException(
            'password=NeverLogThis'
        );

    $exception =
        new DatabaseConnectionException(
            DatabaseConnectionFailureType::TIMEOUT,
            $rawException
        );

    expect(
        $exception->context()
    )->toBe([
        'database_connection_failure_type' => 'TIMEOUT',
    ]);
});
