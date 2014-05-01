<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\Http;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class HttpCheck implements HealthCheck
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout = 5,
        private readonly int $expectedStatus = 200,
        private readonly string $checkName = 'http',
    ) {}

    public function name(): string
    {
        return $this->checkName;
    }

    public function check(): CheckResult
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->url);
            $statusCode = $response->status();

            if ($statusCode !== $this->expectedStatus) {
                return CheckResult::critical(
                    $this->name(),
                    "HTTP check failed: expected {$this->expectedStatus}, got {$statusCode}.",
                    ['url' => $this->url, 'status' => $statusCode],
                );
            }

            return CheckResult::ok(
                $this->name(),
                "HTTP endpoint responded with {$statusCode}.",
                ['url' => $this->url, 'status' => $statusCode],
            );
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'HTTP check failed: '.$e->getMessage(),
                ['url' => $this->url],
            );
        }
    }
}
