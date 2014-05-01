<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

use Illuminate\Support\Facades\Cache;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class HealthService
{
    /** @var HealthCheck[] */
    private array $checks = [];

    private int $timeout;

    /** @var array<string, mixed> */
    private array $cacheConfig;

    /**
     * @param  array<string, mixed>  $cacheConfig
     */
    public function __construct(int $timeout = 5, array $cacheConfig = [])
    {
        $this->timeout = $timeout;
        $this->cacheConfig = array_merge(['enabled' => false, 'ttl' => 30], $cacheConfig);
    }

    public function register(HealthCheck $check): static
    {
        $this->checks[$check->name()] = $check;

        return $this;
    }

    public function runAll(): HealthReport
    {
        if ($this->cacheConfig['enabled']) {
            $cacheKey = 'laravel_healthcheck_report';

            /** @var HealthReport|null $cached */
            $cached = Cache::get($cacheKey);

            if ($cached instanceof HealthReport) {
                return $cached;
            }
        }

        $start = microtime(true);

        $results = [];

        foreach ($this->checks as $check) {
            $results[] = $this->runWithTimeout($check);
        }

        $durationMs = (microtime(true) - $start) * 1000;
        $report = new HealthReport($results, $durationMs);

        if ($this->cacheConfig['enabled']) {
            Cache::put($cacheKey, $report, (int) $this->cacheConfig['ttl']);
        }

        return $report;
    }

    public function runCheck(string $name): CheckResult
    {
        if (! isset($this->checks[$name])) {
            return CheckResult::critical($name, "No check registered with name '{$name}'.");
        }

        return $this->runWithTimeout($this->checks[$name]);
    }

    private function runWithTimeout(HealthCheck $check): CheckResult
    {
        try {
            $result = $this->executeWithTimeout($check);
        } catch (Throwable $e) {
            $result = CheckResult::critical(
                $check->name(),
                'Check threw an exception: '.$e->getMessage(),
                ['exception' => get_class($e)],
            );
        }

        return $result;
    }

    private function executeWithTimeout(HealthCheck $check): CheckResult
    {
        // PHP does not natively support per-call timeouts without pcntl.
        // We use set_time_limit only when pcntl_alarm is unavailable (non-CLI) or
        // when running in CLI we use SIGALRM via pcntl if available.
        if (function_exists('pcntl_alarm') && function_exists('pcntl_signal')) {
            return $this->executeWithPcntlTimeout($check);
        }

        // Fallback: no hard timeout enforcement; the main PHP request timeout applies.
        return $check->check();
    }

    private function executeWithPcntlTimeout(HealthCheck $check): CheckResult
    {
        $timedOut = false;

        pcntl_signal(SIGALRM, function () use (&$timedOut): void {
            $timedOut = true;
        });

        pcntl_alarm($this->timeout);

        try {
            $result = $check->check();
        } catch (Throwable $e) {
            pcntl_alarm(0);

            if ($timedOut) {
                return CheckResult::critical(
                    $check->name(),
                    "Check timed out after {$this->timeout} seconds.",
                );
            }

            throw $e;
        }

        pcntl_alarm(0);

        if ($timedOut) {
            return CheckResult::critical(
                $check->name(),
                "Check timed out after {$this->timeout} seconds.",
            );
        }

        return $result;
    }

    /** @return HealthCheck[] */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
