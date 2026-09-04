<?php

use App\Models\DatabaseConnection;
use App\Services\DatabaseConnectorService;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('generates different runtime connection names for different targets', function () {
    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('runtimeConnectionName');

    $first = $method->invoke($service, [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'database_one',
        'username' => 'monitor',
    ]);

    $second = $method->invoke($service, [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'database_two',
        'username' => 'monitor',
    ]);

    expect($first)
        ->not->toBe($second)
        ->and($first)
        ->toStartWith('monitoring_')
        ->and($second)
        ->toStartWith('monitoring_');
});

it('does not include credentials or target details in runtime connection name', function () {
    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('runtimeConnectionName');

    $name = $method->invoke($service, [
        'driver' => 'mysql',
        'host' => 'db.internal.example',
        'port' => 3306,
        'database' => 'customer_database',
        'username' => 'secret_monitor_user',
        'password' => 'super-secret-password',
    ]);

    expect($name)
        ->toStartWith('monitoring_')
        ->and($name)
        ->not->toContain('db.internal.example')
        ->and($name)
        ->not->toContain('customer_database')
        ->and($name)
        ->not->toContain('secret_monitor_user')
        ->and($name)
        ->not->toContain('super-secret-password');
});

it('can remove runtime connection configuration', function () {
    Config::set('database.connections.monitoring_test_cleanup', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
    ]);

    expect(
        Config::get(
            'database.connections.monitoring_test_cleanup'
        )
    )->not->toBeNull();

    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod(
        'cleanupRuntimeConnection'
    );

    $method->invoke(
        $service,
        'monitoring_test_cleanup'
    );

    expect(
        Config::get(
            'database.connections.monitoring_test_cleanup'
        )
    )->toBeNull();
});

it('can release a runtime database connection', function () {
    $service = app(DatabaseConnectorService::class);

    Config::set(
        'database.connections.monitoring_release_test',
        [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]
    );

    $connection = DB::connection(
        'monitoring_release_test'
    );

    expect(
        Config::get(
            'database.connections.monitoring_release_test'
        )
    )->not->toBeNull();

    $service->release($connection);

    expect(
        Config::get(
            'database.connections.monitoring_release_test'
        )
    )->toBeNull();
});

it('release ignores non monitoring connections', function () {
    $service = app(DatabaseConnectorService::class);

    $connection = DB::connection();

    $defaultConnectionName = $connection->getName();

    $before = Config::get(
        "database.connections.{$defaultConnectionName}"
    );

    $service->release($connection);

    expect(
        Config::get(
            "database.connections.{$defaultConnectionName}"
        )
    )->toBe($before);
});

it('releases a successful connection test', function () {
    $databaseConnection = Mockery::mock(
        DatabaseConnection::class
    );

    $db = Mockery::mock(
        Connection::class
    );

    $service = Mockery::mock(
        DatabaseConnectorService::class
    )->makePartial();

    $service->shouldReceive('connect')
        ->once()
        ->with($databaseConnection)
        ->andReturn($db);

    $service->shouldReceive('release')
        ->once()
        ->with($db);

    expect(
        $service->test($databaseConnection)
    )->toBeTrue();
});

it('releases an operation scoped connection after success', function () {
    $databaseConnection =
        Mockery::mock(
            DatabaseConnection::class
        );

    $db = DB::connection();

    $service =
        Mockery::mock(
            DatabaseConnectorService::class
        )->makePartial();

    $service->shouldReceive('connect')
        ->once()
        ->with($databaseConnection)
        ->andReturn($db);

    $service->shouldReceive('release')
        ->once()
        ->with($db);

    $result = $service->withConnection(
        $databaseConnection,
        function ($runtimeConnection) use ($db) {
            expect($runtimeConnection)
                ->toBe($db);

            return 'completed';
        }
    );

    expect($result)->toBe('completed');
});

it('releases an operation scoped connection after an exception', function () {
    $databaseConnection =
        Mockery::mock(
            DatabaseConnection::class
        );

    $db = DB::connection();

    $service =
        Mockery::mock(
            DatabaseConnectorService::class
        )->makePartial();

    $service->shouldReceive('connect')
        ->once()
        ->with($databaseConnection)
        ->andReturn($db);

    $service->shouldReceive('release')
        ->once()
        ->with($db);

    expect(
        fn () => $service->withConnection(
            $databaseConnection,
            function () {
                throw new RuntimeException(
                    'operation failed'
                );
            }
        )
    )->toThrow(
        RuntimeException::class,
        'operation failed'
    );
});

it('generates different runtime names for repeated connections to the same target', function () {
    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod(
        'runtimeConnectionName'
    );

    $data = [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'same_database',
        'username' => 'monitor',
        'password' => 'secret',
        'schema' => null,
    ];

    $first = $method->invoke(
        $service,
        $data
    );

    $second = $method->invoke(
        $service,
        $data
    );

    expect($first)
        ->not->toBe($second)
        ->and($first)
        ->toStartWith('monitoring_')
        ->and($second)
        ->toStartWith('monitoring_');
});
