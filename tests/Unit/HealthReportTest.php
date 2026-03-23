<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\HealthReport;
use PhilipRehberger\Healthcheck\HealthStatus;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class HealthReportTest extends TestCase
{
    public function test_all_ok_checks_produce_ok_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::ok('cache'),
        ], 12.5);

        $this->assertSame(HealthStatus::Ok, $report->status);
        $this->assertTrue($report->isHealthy());
    }

    public function test_any_critical_check_produces_critical_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::critical('cache', 'Down.'),
        ], 5.0);

        $this->assertSame(HealthStatus::Critical, $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_warning_without_critical_produces_degraded_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::warning('environment', 'Debug on.'),
        ], 3.0);

        $this->assertSame(HealthStatus::Degraded, $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_critical_takes_precedence_over_warning(): void
    {
        $report = new HealthReport([
            CheckResult::warning('environment', 'Debug on.'),
            CheckResult::critical('cache', 'Down.'),
        ], 3.0);

        $this->assertSame(HealthStatus::Critical, $report->status);
    }

    public function test_degraded_check_produces_degraded_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::degraded('cache', 'High latency.'),
        ], 4.0);

        $this->assertSame(HealthStatus::Degraded, $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_critical_takes_precedence_over_degraded(): void
    {
        $report = new HealthReport([
            CheckResult::degraded('cache', 'High latency.'),
            CheckResult::critical('database', 'Connection refused.'),
        ], 3.0);

        $this->assertSame(HealthStatus::Critical, $report->status);
    }

    public function test_degraded_and_warning_both_produce_degraded_status(): void
    {
        $report = new HealthReport([
            CheckResult::degraded('cache', 'High latency.'),
            CheckResult::warning('environment', 'Debug on.'),
        ], 3.0);

        $this->assertSame(HealthStatus::Degraded, $report->status);
    }

    public function test_to_array_structure(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database', 'Healthy.'),
        ], 8.123);

        $array = $report->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('duration_ms', $array);
        $this->assertArrayHasKey('checks', $array);
        $this->assertCount(1, $array['checks']);
        $this->assertSame('ok', $array['checks'][0]['status']);
    }

    public function test_to_json_produces_valid_json(): void
    {
        $report = new HealthReport([CheckResult::ok('database')], 1.0);

        $json = $report->toJson();

        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('status', $decoded);
    }

    public function test_empty_checks_produce_ok_status(): void
    {
        $report = new HealthReport([], 0.0);

        $this->assertSame(HealthStatus::Ok, $report->status);
        $this->assertTrue($report->isHealthy());
    }

    public function test_duration_is_rounded_in_array(): void
    {
        $report = new HealthReport([], 12.123456);

        $this->assertSame(12.12, $report->toArray()['duration_ms']);
    }

    public function test_to_array_status_is_string(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
        ], 1.0);

        $this->assertSame('ok', $report->toArray()['status']);
    }

    public function test_get_metrics_returns_empty_when_no_metrics(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::ok('cache'),
        ], 1.0);

        $this->assertSame([], $report->getMetrics());
    }

    public function test_get_metrics_aggregates_check_metrics(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database')->withMetrics(['latency_ms' => 5, 'connections' => 10]),
            CheckResult::ok('cache')->withMetrics(['hit_rate' => 0.95]),
            CheckResult::ok('storage'),
        ], 1.0);

        $metrics = $report->getMetrics();

        $this->assertCount(2, $metrics);
        $this->assertSame(['latency_ms' => 5, 'connections' => 10], $metrics['database']);
        $this->assertSame(['hit_rate' => 0.95], $metrics['cache']);
    }

    public function test_health_status_enum_values(): void
    {
        $this->assertSame('ok', HealthStatus::Ok->value);
        $this->assertSame('degraded', HealthStatus::Degraded->value);
        $this->assertSame('critical', HealthStatus::Critical->value);
    }

    public function test_health_status_enum_from_string(): void
    {
        $this->assertSame(HealthStatus::Ok, HealthStatus::from('ok'));
        $this->assertSame(HealthStatus::Degraded, HealthStatus::from('degraded'));
        $this->assertSame(HealthStatus::Critical, HealthStatus::from('critical'));
    }
}
