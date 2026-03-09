<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\HealthReport;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class HealthReportTest extends TestCase
{
    public function test_all_ok_checks_produce_ok_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::ok('cache'),
        ], 12.5);

        $this->assertSame('ok', $report->status);
        $this->assertTrue($report->isHealthy());
    }

    public function test_any_critical_check_produces_critical_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::critical('cache', 'Down.'),
        ], 5.0);

        $this->assertSame('critical', $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_warning_without_critical_produces_warning_status(): void
    {
        $report = new HealthReport([
            CheckResult::ok('database'),
            CheckResult::warning('environment', 'Debug on.'),
        ], 3.0);

        $this->assertSame('warning', $report->status);
        $this->assertFalse($report->isHealthy());
    }

    public function test_critical_takes_precedence_over_warning(): void
    {
        $report = new HealthReport([
            CheckResult::warning('environment', 'Debug on.'),
            CheckResult::critical('cache', 'Down.'),
        ], 3.0);

        $this->assertSame('critical', $report->status);
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

        $this->assertSame('ok', $report->status);
        $this->assertTrue($report->isHealthy());
    }

    public function test_duration_is_rounded_in_array(): void
    {
        $report = new HealthReport([], 12.123456);

        $this->assertSame(12.12, $report->toArray()['duration_ms']);
    }
}
