<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\Cache;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class CacheCheck implements HealthCheck
{
    private const TEST_KEY = '_healthcheck_cache_probe';

    private const TEST_VALUE = 'ok';

    private const TTL = 10;

    public function name(): string
    {
        return 'cache';
    }

    public function check(): CheckResult
    {
        try {
            $testValue = self::TEST_VALUE;

            try {
                Cache::put(self::TEST_KEY, $testValue, self::TTL);
                $value = Cache::get(self::TEST_KEY);
            } finally {
                Cache::forget(self::TEST_KEY);
            }

            if ($value !== $testValue) {
                return CheckResult::critical(
                    $this->name(),
                    'Cache read returned unexpected value.',
                );
            }

            return CheckResult::ok($this->name(), 'Cache is healthy.');
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'Cache check failed: '.$e->getMessage(),
            );
        }
    }
}
