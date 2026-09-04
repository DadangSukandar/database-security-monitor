<?php

use App\Services\DatabaseConnectorService;
use Illuminate\Support\Facades\Config;

it('configures a bounded mysql connection timeout', function () {
    Config::set('security.monitoring_connect_timeout', 5);

    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('connectionConfig');

    $config = $method->invoke($service, [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'example',
        'username' => 'example',
        'password' => 'secret',
        'schema' => null,
    ]);

    expect($config['options'][PDO::ATTR_TIMEOUT] ?? null)
        ->toBe(5);
});

it('keeps postgres configuration bounded without mysql pdo timeout option', function () {
    Config::set('security.monitoring_connect_timeout', 5);

    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('connectionConfig');

    $config = $method->invoke($service, [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'example',
        'username' => 'example',
        'password' => 'secret',
        'schema' => 'public',
    ]);

    expect($config['options'] ?? [])
        ->not->toHaveKey(PDO::ATTR_TIMEOUT);

    expect($config['connect_timeout'] ?? null)
        ->toBe(5);
});

it('normalizes an invalid monitoring connection timeout', function () {
    Config::set('security.monitoring_connect_timeout', 0);

    $service = app(DatabaseConnectorService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('connectionConfig');

    $config = $method->invoke($service, [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'example',
        'username' => 'example',
        'password' => 'secret',
        'schema' => null,
    ]);

    expect($config['options'][PDO::ATTR_TIMEOUT] ?? null)
        ->toBeGreaterThanOrEqual(1);
});
