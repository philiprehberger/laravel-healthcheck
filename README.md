# Laravel Healthcheck

[![Tests](https://github.com/philiprehberger/laravel-healthcheck/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-healthcheck/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-healthcheck.svg)](https://packagist.org/packages/philiprehberger/laravel-healthcheck)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-healthcheck)](LICENSE)
[![Sponsor](https://img.shields.io/badge/sponsor-GitHub%20Sponsors-ec6cb9)](https://github.com/sponsors/philiprehberger)

Configurable health check endpoint with built-in checks and Kubernetes probe support.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-healthcheck
```

Laravel's package auto-discovery registers the service provider automatically.

### Publishing the config

```bash
php artisan vendor:publish --tag=healthcheck-config
```

This creates `config/healthcheck.php`.

## Usage

### Basic health check

Once installed, the following endpoints are immediately available:

| Endpoint | Description |
|---|---|
| `GET /health` | Full report — `200` if healthy, `503` if not |
| `GET /health/live` | Liveness probe — always `200` |
| `GET /health/ready` | Readiness probe — `200` if healthy, `503` if not |

Example response from `GET /health`:

```json
{
  "status": "ok",
  "duration_ms": 14.52,
  "checks": [
    {
      "name": "database",
      "status": "ok",
      "message": "Database connection is healthy.",
      "meta": { "connection": "mysql" }
    },
    {
      "name": "cache",
      "status": "ok",
      "message": "Cache is healthy.",
      "meta": []
    },
    {
      "name": "environment",
      "status": "ok",
      "message": "Environment configuration looks healthy.",
      "meta": { "env": "production", "debug": false }
    }
  ]
}
```

When any check is **critical** or **warning**, the overall `status` reflects that and the HTTP status code becomes `503`.

### Status levels

| Level | Meaning |
|---|---|
| `ok` | Check passed |
| `degraded` | Partial failure or performance issue |
| `warning` | Non-fatal issue (e.g. debug enabled) |
| `critical` | Check failed — affects readiness |

The overall report status uses the `HealthStatus` enum and resolves as: `critical` if any check is critical; `degraded` if any is degraded or warning (but none critical); `ok` only if all checks pass.

### Configuration

`config/healthcheck.php`:

```php
return [
    'route_prefix' => env('HEALTHCHECK_ROUTE_PREFIX', 'health'),

    'middleware' => [],

    'checks' => [
        \PhilipRehberger\Healthcheck\Checks\DatabaseCheck::class,
        \PhilipRehberger\Healthcheck\Checks\CacheCheck::class,
        \PhilipRehberger\Healthcheck\Checks\StorageCheck::class,
        \PhilipRehberger\Healthcheck\Checks\EnvironmentCheck::class,
    ],

    'timeout' => (int) env('HEALTHCHECK_TIMEOUT', 5),

    'cache' => [
        'enabled' => (bool) env('HEALTHCHECK_CACHE_ENABLED', false),
        'ttl'     => (int) env('HEALTHCHECK_CACHE_TTL', 30),
    ],
];
```

#### Restricting access with middleware

```php
'middleware' => ['auth:sanctum'],
```

Or use a custom IP-allowlist middleware for infrastructure-only access.

#### Enabling result caching

To avoid hammering your database on every probe poll:

```env
HEALTHCHECK_CACHE_ENABLED=true
HEALTHCHECK_CACHE_TTL=30
```

### Built-in checks

#### DatabaseCheck

Tests that a PDO connection can be established.

```php
new DatabaseCheck()                    // uses default connection
new DatabaseCheck('mysql_reporting')   // named connection
```

#### CacheCheck

Writes, reads, and deletes a probe key using the default cache driver.
Resource cleanup is guaranteed — the probe key is always removed via `try-finally`, even if cache operations fail.

#### StorageCheck

Writes, reads, and deletes a probe file on the default filesystem disk.
Resource cleanup is guaranteed — the probe file is always deleted via `try-finally`, even if read or content verification fails.

```php
new StorageCheck()        // default disk
new StorageCheck('s3')    // named disk
```

#### RedisCheck

Calls `PING` on the Redis connection. Returns a warning (not critical) if the Redis extension and Predis are both absent so the check degrades gracefully in environments without Redis.

```php
new RedisCheck()             // default connection
new RedisCheck('cache')      // named connection
```

#### QueueCheck

Resolves the queue connection from the container to verify connectivity.

```php
new QueueCheck()             // default connection
new QueueCheck('redis')      // named connection
```

#### EnvironmentCheck

Returns a **warning** when `APP_DEBUG=true` in a production environment.

#### HttpCheck

Pings an external URL and verifies the response status code.

```php
new HttpCheck('https://api.stripe.com')
new HttpCheck('https://api.stripe.com', timeout: 3, expectedStatus: 200, checkName: 'stripe')
```

Register via the service container for constructor-injected checks:

```php
// AppServiceProvider::register()
$this->app->bind(\PhilipRehberger\Healthcheck\Checks\HttpCheck::class, fn () =>
    new \PhilipRehberger\Healthcheck\Checks\HttpCheck(
        url: 'https://api.stripe.com',
        checkName: 'stripe_api',
    )
);
```

Then add the class string to `config/healthcheck.php` `checks` array.

### Writing custom checks

Implement the `HealthCheck` contract:

```php
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;

class ElasticsearchCheck implements HealthCheck
{
    public function name(): string
    {
        return 'elasticsearch';
    }

    public function check(): CheckResult
    {
        try {
            $info = app('elasticsearch')->info();

            return CheckResult::ok(
                $this->name(),
                'Elasticsearch is healthy.',
                ['version' => $info['version']['number']],
            );
        } catch (\Throwable $e) {
            return CheckResult::critical($this->name(), $e->getMessage());
        }
    }
}
```

Register in `config/healthcheck.php`:

```php
'checks' => [
    // ...
    ElasticsearchCheck::class,
],
```

### Degraded status

Use the `degraded` factory when a check is partially failing or experiencing performance issues:

```php
public function check(): CheckResult
{
    $latency = $this->measureLatency();

    if ($latency > 1000) {
        return CheckResult::degraded(
            $this->name(),
            'High latency detected.',
            ['latency_ms' => $latency],
        );
    }

    return CheckResult::ok($this->name(), 'Healthy.', ['latency_ms' => $latency]);
}
```

### Attaching metrics

Attach arbitrary metrics to any check result using `withMetrics()`. Metrics are aggregated on the report via `getMetrics()`:

```php
$result = CheckResult::ok('database', 'Healthy.')
    ->withMetrics([
        'latency_ms' => 5,
        'connections' => 10,
        'pool_usage' => 0.42,
    ]);

// In the report:
$report = $healthService->runAll();
$metrics = $report->getMetrics();
// ['database' => ['latency_ms' => 5, 'connections' => 10, 'pool_usage' => 0.42]]
```

### Kubernetes probe configuration

#### Deployment manifest

```yaml
livenessProbe:
  httpGet:
    path: /health/live
    port: 8080
  initialDelaySeconds: 10
  periodSeconds: 15
  failureThreshold: 3

readinessProbe:
  httpGet:
    path: /health/ready
    port: 8080
  initialDelaySeconds: 5
  periodSeconds: 10
  failureThreshold: 2
```

**Liveness** (`/health/live`) — always returns `200` as long as PHP-FPM/Octane is alive. Kubernetes will restart the container only if this stops responding, not on application-level failures.

**Readiness** (`/health/ready`) — returns `200` only when all health checks pass. Kubernetes removes the pod from load balancer rotation while this returns `503`, enabling zero-downtime deploys during database migrations or cold-start delays.

#### Ingress — skip auth middleware on probe endpoints

If you add auth middleware globally, exclude the probe paths in your ingress or use a separate middleware group:

```php
// config/healthcheck.php
'route_prefix' => 'health',
'middleware'   => [],   // no auth on probes — protect at the network level instead
```

## API

### `HealthCheck` (Interface)

| Method | Return Type | Description |
|--------|-------------|-------------|
| `name(): string` | `string` | Unique check identifier |
| `check(): CheckResult` | `CheckResult` | Execute the check and return a result |

### `HealthStatus` (Enum)

| Case | Value | Description |
|------|-------|-------------|
| `HealthStatus::Ok` | `'ok'` | All checks passed |
| `HealthStatus::Degraded` | `'degraded'` | Partial failure or performance issue |
| `HealthStatus::Critical` | `'critical'` | One or more checks failed |

### `CheckResult`

| Method | Description |
|--------|-------------|
| `CheckResult::ok(string $name, string $message, array $meta)` | Passing result |
| `CheckResult::degraded(string $name, string $message, array $meta)` | Degraded/partial failure |
| `CheckResult::warning(string $name, string $message, array $meta)` | Non-fatal warning |
| `CheckResult::critical(string $name, string $message, array $meta)` | Failing result |
| `$result->withMetrics(array $metrics): CheckResult` | Attach metrics to a result |

### `HealthReport`

| Method | Return Type | Description |
|--------|-------------|-------------|
| `$report->status` | `HealthStatus` | Overall status enum |
| `$report->isHealthy()` | `bool` | `true` when status is `Ok` |
| `$report->getMetrics()` | `array` | Aggregated metrics from all checks |
| `$report->toArray()` | `array` | Full report as array |
| `$report->toJson(int $flags)` | `string` | Full report as JSON |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
