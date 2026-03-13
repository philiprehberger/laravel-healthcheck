<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\Redis;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Predis\Client;
use Throwable;

class RedisCheck implements HealthCheck
{
    public function __construct(private readonly string $connection = 'default') {}

    public function name(): string
    {
        return 'redis';
    }

    public function check(): CheckResult
    {
        if (! $this->isRedisAvailable()) {
            return CheckResult::warning(
                $this->name(),
                'Redis extension or driver is not available.',
            );
        }

        try {
            $response = Redis::connection($this->connection)->ping();

            // Depending on the driver, ping() returns true, 1, or the string "+PONG".
            $isOk = $response === true
                || $response === 1
                || (is_string($response) && strtoupper(trim($response, '+')) === 'PONG');

            if (! $isOk) {
                return CheckResult::critical(
                    $this->name(),
                    'Redis ping returned unexpected response.',
                    ['response' => $response],
                );
            }

            return CheckResult::ok(
                $this->name(),
                'Redis is healthy.',
                ['connection' => $this->connection],
            );
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'Redis check failed: '.$e->getMessage(),
                ['connection' => $this->connection],
            );
        }
    }

    private function isRedisAvailable(): bool
    {
        return extension_loaded('redis') || class_exists(Client::class);
    }
}
