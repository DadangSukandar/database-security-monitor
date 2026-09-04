<?php

use App\Enums\DatabaseConnectionFailureType;
use App\Exceptions\DatabaseConnectionException;
use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectionFailureClassifier;
use App\Services\DatabaseConnectorService;
use RuntimeException;

it('wraps a classified failure without retaining the original exception', function () {
    $original = new RuntimeException(
        "SQLSTATE[HY000] [1045] Access denied for user 'secret-user'"
    );

    $classifier = app(DatabaseConnectionFailureClassifier::class);

    $exception = new DatabaseConnectionException(
        $classifier->classify($original),
        $original
    );

    expect($exception->failureType)
        ->toBe(DatabaseConnectionFailureType::AUTHENTICATION_FAILED)
        ->and($exception->getMessage())
        ->toBe('Database monitoring connection failed.')
        ->and($exception->getMessage())
        ->not->toContain('secret-user')
        ->and($exception->getPrevious())
        ->toBeNull();
});

it('classifies failures raised while establishing a database connection', function () {
    $connection = Mockery::mock(DatabaseConnection::class)
        ->makePartial();

    $connection->driver = 'unsupported-driver';
    $connection->host = '127.0.0.1';
    $connection->port = 1234;
    $connection->database = 'example';
    $connection->username = 'example';
    $connection->schema = null;

    $connection->shouldReceive('getDecryptedPassword')
        ->once()
        ->andReturn('secret');

    $service = app(DatabaseConnectorService::class);

    try {
        $service->connect($connection);

        $this->fail(
            'Expected DatabaseConnectionException was not thrown.'
        );
    } catch (DatabaseConnectionException $exception) {
        expect($exception->failureType)
            ->toBe(DatabaseConnectionFailureType::UNKNOWN)
            ->and($exception->getMessage())
            ->toBe('Database monitoring connection failed.')
            ->and($exception->getMessage())
            ->not->toContain('secret');
    }
});
