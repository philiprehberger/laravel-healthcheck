<?php

declare(strict_types=1);
use PhilipRehberger\Healthcheck\Checks\CacheCheck;
use PhilipRehberger\Healthcheck\Checks\DatabaseCheck;
use PhilipRehberger\Healthcheck\Checks\EnvironmentCheck;
use PhilipRehberger\Healthcheck\Checks\StorageCheck;

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for health check routes. With the default value of "health"
    | the following routes are registered:
    |
    |   GET /health          — full health report
    |   GET /health/live     — Kubernetes liveness probe (always 200)
    |   GET /health/ready    — Kubernetes readiness probe (200 or 503)
    |
    */
    'route_prefix' => env('HEALTHCHECK_ROUTE_PREFIX', 'health'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to all health check routes. You may restrict access
    | to these endpoints by adding middleware such as 'auth:sanctum' or a
    | custom IP-allowlist middleware.
    |
    */
    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Registered Checks
    |--------------------------------------------------------------------------
    |
    | List of check classes that will be executed when a health check request
    | comes in. Each class must implement HealthCheck interface. You may add
    | or remove entries and supply constructor arguments by using a closure or
    | by registering the class in the service container beforehand.
    |
    */
    'checks' => [
        DatabaseCheck::class,
        CacheCheck::class,
        StorageCheck::class,
        EnvironmentCheck::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Check Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a single check to complete before
    | marking it as critical. Only enforced when the pcntl extension is loaded.
    |
    */
    'timeout' => (int) env('HEALTHCHECK_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Result Caching
    |--------------------------------------------------------------------------
    |
    | Enable caching to avoid running checks on every request. Results are
    | stored using the default cache driver.
    |
    | ttl: Cache lifetime in seconds.
    |
    */
    'cache' => [
        'enabled' => (bool) env('HEALTHCHECK_CACHE_ENABLED', false),
        'ttl' => (int) env('HEALTHCHECK_CACHE_TTL', 30),
    ],

];
