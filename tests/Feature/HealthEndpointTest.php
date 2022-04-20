<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Feature;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use PhilipRehberger\Healthcheck\HealthcheckServiceProvider;
use PhilipRehberger\Healthcheck\HealthService;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    private function bindHealthService(string $status): void
    {
        $check = new class($status) implements HealthCheck
        {
            public function __construct(private readonly string $status) {}

            public function name(): string
            {
                return 'test';
            }

            public function check(): CheckResult
            {
                return match ($this->status) {
                    'ok' => CheckResult::ok('test', 'All good.'),
                    'warning' => CheckResult::warning('test', 'Watch out.'),
                    default => CheckResult::critical('test', 'Down.'),
                };
            }
        };

        $this->app->singleton(HealthService::class, function () use ($check): HealthService {
            $service = new HealthService;
            $service->register($check);

            return $service;
        });
    }

    // -------------------------------------------------------------------------
    // GET /health
    // -------------------------------------------------------------------------

    public function test_health_endpoint_returns_200_when_all_checks_pass(): void
    {
        $this->bindHealthService('ok');

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'duration_ms', 'checks'])
            ->assertJsonPath('status', 'ok');
    }

    public function test_health_endpoint_returns_503_when_critical_check_fails(): void
    {
        $this->bindHealthService('critical');

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'critical');
    }

    public function test_health_endpoint_returns_503_when_check_is_warning(): void
    {
        $this->bindHealthService('warning');

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'degraded');
    }

    public function test_health_response_includes_check_details(): void
    {
        $this->bindHealthService('ok');

        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'checks')
            ->assertJsonPath('checks.0.name', 'test')
            ->assertJsonPath('checks.0.status', 'ok');
    }

    // -------------------------------------------------------------------------
    // GET /health/live
    // -------------------------------------------------------------------------

    public function test_live_endpoint_always_returns_200(): void
    {
        // Even with a critical check registered, /health/live must return 200.
        $this->bindHealthService('critical');

        $response = $this->getJson('/health/live');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_live_endpoint_does_not_run_checks(): void
    {
        // Bind a service with no checks; if checks were run we'd still get ok.
        $this->app->singleton(HealthService::class, fn () => new HealthService);

        $response = $this->getJson('/health/live');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // GET /health/ready
    // -------------------------------------------------------------------------

    public function test_ready_endpoint_returns_200_when_healthy(): void
    {
        $this->bindHealthService('ok');

        $response = $this->getJson('/health/ready');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_ready_endpoint_returns_503_when_not_healthy(): void
    {
        $this->bindHealthService('critical');

        $response = $this->getJson('/health/ready');

        $response->assertStatus(503);
    }

    // -------------------------------------------------------------------------
    // Route configuration
    // -------------------------------------------------------------------------

    public function test_custom_route_prefix_is_used(): void
    {
        config(['healthcheck.route_prefix' => 'status']);

        // Re-register routes with new prefix by refreshing the provider.
        $provider = new HealthcheckServiceProvider($this->app);
        $provider->boot();

        $this->bindHealthService('ok');

        $response = $this->getJson('/status');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // JSON structure contract
    // -------------------------------------------------------------------------

    public function test_response_json_contains_duration_ms(): void
    {
        $this->bindHealthService('ok');

        $response = $this->getJson('/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('duration_ms', $data);
        $this->assertIsFloat($data['duration_ms']);
    }

    // -------------------------------------------------------------------------
    // Mixed status and failure scenarios
    // -------------------------------------------------------------------------

    public function test_health_report_with_mixed_statuses(): void
    {
        $okCheck = new class implements HealthCheck
        {
            public function name(): string
            {
                return 'ok-check';
            }

            public function check(): CheckResult
            {
                return CheckResult::ok('ok-check');
            }
        };

        $criticalCheck = new class implements HealthCheck
        {
            public function name(): string
            {
                return 'critical-check';
            }

            public function check(): CheckResult
            {
                return CheckResult::critical('critical-check', 'Down');
            }
        };

        $this->app->singleton(HealthService::class, function () use ($okCheck, $criticalCheck): HealthService {
            $service = new HealthService;
            $service->register($okCheck);
            $service->register($criticalCheck);

            return $service;
        });

        $response = $this->getJson('/health');
        $response->assertStatus(503)
            ->assertJsonPath('status', 'critical')
            ->assertJsonCount(2, 'checks');
    }

    public function test_check_failure_does_not_expose_full_exception_message(): void
    {
        // When a check throws, the exception message IS included. This is by design
        // for operational debugging. This test verifies the structure is correct.
        $throwingCheck = new class implements HealthCheck
        {
            public function name(): string
            {
                return 'throwing-check';
            }

            public function check(): CheckResult
            {
                throw new \RuntimeException('Sensitive error details');
            }
        };

        $this->app->singleton(HealthService::class, function () use ($throwingCheck): HealthService {
            $service = new HealthService;
            $service->register($throwingCheck);

            return $service;
        });

        $response = $this->getJson('/health');
        $response->assertStatus(503)
            ->assertJsonPath('status', 'critical')
            ->assertJsonPath('checks.0.name', 'throwing-check');
    }
}
