<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use PhilipRehberger\Healthcheck\HealthReport;
use PhilipRehberger\Healthcheck\HealthService;
use PhilipRehberger\Healthcheck\HealthStatus;
use PhilipRehberger\Healthcheck\Tests\TestCase;
use RuntimeException;

class HealthServiceTest extends TestCase
{
    private function makeCheck(string $name, CheckResult $result): HealthCheck
    {
        return new class($name, $result) implements HealthCheck
        {
            public function __construct(
                private readonly string $checkName,
                private readonly CheckResult $result,
            ) {}

            public function name(): string
            {
                return $this->checkName;
            }

            public function check(): CheckResult
            {
                return $this->result;
            }
        };
    }

    private function makeThrowingCheck(string $name): HealthCheck
    {
        return new class($name) implements HealthCheck
        {
            public function __construct(private readonly string $checkName) {}

            public function name(): string
            {
                return $this->checkName;
            }

            public function check(): CheckResult
            {
                throw new RuntimeException('Something went wrong.');
            }
        };
    }

    public function test_run_all_returns_health_report(): void
    {
        $service = new HealthService;
        $service->register($this->makeCheck('database', CheckResult::ok('database')));
        $service->register($this->makeCheck('cache', CheckResult::ok('cache')));

        $report = $service->runAll();

        $this->assertInstanceOf(HealthReport::class, $report);
        $this->assertCount(2, $report->checks);
        $this->assertSame(HealthStatus::Ok, $report->status);
    }

    public function test_run_all_aggregates_critical_status(): void
    {
        $service = new HealthService;
        $service->register($this->makeCheck('database', CheckResult::ok('database')));
        $service->register($this->makeCheck('cache', CheckResult::critical('cache', 'Down.')));

        $report = $service->runAll();

        $this->assertSame(HealthStatus::Critical, $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_run_check_by_name_returns_result(): void
    {
        $service = new HealthService;
        $service->register($this->makeCheck('database', CheckResult::ok('database', 'Healthy.')));

        $result = $service->runCheck('database');

        $this->assertSame('database', $result->name);
        $this->assertSame('ok', $result->status);
    }

    public function test_run_check_returns_critical_for_unknown_name(): void
    {
        $service = new HealthService;

        $result = $service->runCheck('nonexistent');

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('nonexistent', $result->message);
    }

    public function test_exception_in_check_returns_critical_result(): void
    {
        $service = new HealthService;
        $service->register($this->makeThrowingCheck('flaky'));

        $report = $service->runAll();

        $this->assertSame(HealthStatus::Critical, $report->status);
        $result = $report->checks[0];
        $this->assertSame('flaky', $result->name);
        $this->assertStringContainsString('Something went wrong.', $result->message);
    }

    public function test_register_returns_fluent_service_instance(): void
    {
        $service = new HealthService;
        $returned = $service->register($this->makeCheck('test', CheckResult::ok('test')));

        $this->assertSame($service, $returned);
    }

    public function test_get_checks_returns_registered_checks(): void
    {
        $service = new HealthService;
        $check = $this->makeCheck('database', CheckResult::ok('database'));
        $service->register($check);

        $checks = $service->getChecks();

        $this->assertArrayHasKey('database', $checks);
        $this->assertSame($check, $checks['database']);
    }

    public function test_cache_returns_same_report_on_second_call(): void
    {
        $service = new HealthService(5, ['enabled' => true, 'ttl' => 60]);
        $service->register($this->makeCheck('database', CheckResult::ok('database')));

        $report1 = $service->runAll();
        $report2 = $service->runAll();

        $this->assertSame($report1, $report2);
    }

    public function test_run_all_with_no_checks_returns_ok_report(): void
    {
        $service = new HealthService;

        $report = $service->runAll();

        $this->assertInstanceOf(HealthReport::class, $report);
        $this->assertSame(HealthStatus::Ok, $report->status);
        $this->assertCount(0, $report->checks);
    }
}
