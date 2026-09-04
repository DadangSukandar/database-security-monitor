<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Services\DatabaseConnectionFailureClassifier;
use RuntimeException;

it('classifies database connection failures', function (
    string $message,
    DatabaseConnectionFailureType $expected
): void {
    $classifier = app(DatabaseConnectionFailureClassifier::class);

    $exception = new RuntimeException($message);

    expect($classifier->classify($exception))->toBe($expected);
})->with([
    'mysql timeout' => [
        'SQLSTATE[HY000] [2002] Connection timed out',
        DatabaseConnectionFailureType::TIMEOUT,
    ],

    'mysql authentication failure' => [
        "SQLSTATE[HY000] [1045] Access denied for user 'monitor'",
        DatabaseConnectionFailureType::AUTHENTICATION_FAILED,
    ],

    'postgres authentication failure' => [
        'password authentication failed for user "monitor"',
        DatabaseConnectionFailureType::AUTHENTICATION_FAILED,
    ],

    'connection refused' => [
        'SQLSTATE[HY000] [2002] Connection refused',
        DatabaseConnectionFailureType::HOST_UNREACHABLE,
    ],

    'postgres unreachable' => [
        'could not connect to server: Connection refused',
        DatabaseConnectionFailureType::HOST_UNREACHABLE,
    ],

    'unknown mysql database' => [
        "SQLSTATE[HY000] [1049] Unknown database 'missing_database'",
        DatabaseConnectionFailureType::DATABASE_UNAVAILABLE,
    ],

    'postgres database missing' => [
        'database "missing_database" does not exist',
        DatabaseConnectionFailureType::DATABASE_UNAVAILABLE,
    ],

    'tls failure' => [
        'SSL certificate verify failed',
        DatabaseConnectionFailureType::TLS_ERROR,
    ],

    'read only setup failure' => [
        'Unable to configure read-only session',
        DatabaseConnectionFailureType::READ_ONLY_SETUP_FAILED,
    ],

    'unknown failure' => [
        'Unexpected database driver failure',
        DatabaseConnectionFailureType::UNKNOWN,
    ],
]);
