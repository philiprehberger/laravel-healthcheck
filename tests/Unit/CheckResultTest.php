<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class CheckResultTest extends TestCase
{
    public function test_ok_factory_creates_ok_result(): void
    {
        $result = CheckResult::ok('database', 'All good.', ['host' => 'localhost']);

        $this->assertSame('database', $result->name);
        $this->assertSame(CheckResult::STATUS_OK, $result->status);
        $this->assertSame('All good.', $result->message);
        $this->assertSame(['host' => 'localhost'], $result->meta);
        $this->assertTrue($result->isOk());
        $this->assertFalse($result->isWarning());
        $this->assertFalse($result->isDegraded());
        $this->assertFalse($result->isCritical());
    }

    public function test_warning_factory_creates_warning_result(): void
    {
        $result = CheckResult::warning('environment', 'Debug enabled.');

        $this->assertSame(CheckResult::STATUS_WARNING, $result->status);
        $this->assertTrue($result->isWarning());
        $this->assertFalse($result->isOk());
        $this->assertFalse($result->isDegraded());
        $this->assertFalse($result->isCritical());
    }

    public function test_degraded_factory_creates_degraded_result(): void
    {
        $result = CheckResult::degraded('cache', 'High latency.');

        $this->assertSame(CheckResult::STATUS_DEGRADED, $result->status);
        $this->assertSame('cache', $result->name);
        $this->assertSame('High latency.', $result->message);
        $this->assertTrue($result->isDegraded());
        $this->assertFalse($result->isOk());
        $this->assertFalse($result->isWarning());
        $this->assertFalse($result->isCritical());
    }

    public function test_degraded_factory_with_meta(): void
    {
        $result = CheckResult::degraded('cache', 'Slow.', ['latency_ms' => 500]);

        $this->assertSame(['latency_ms' => 500], $result->meta);
    }

    public function test_critical_factory_creates_critical_result(): void
    {
        $result = CheckResult::critical('cache', 'Connection refused.');

        $this->assertSame(CheckResult::STATUS_CRITICAL, $result->status);
        $this->assertTrue($result->isCritical());
        $this->assertFalse($result->isOk());
        $this->assertFalse($result->isWarning());
        $this->assertFalse($result->isDegraded());
    }

    public function test_defaults_are_empty_string_and_empty_array(): void
    {
        $result = CheckResult::ok('test');

        $this->assertSame('', $result->message);
        $this->assertSame([], $result->meta);
        $this->assertSame([], $result->metrics);
    }

    public function test_to_array_contains_all_fields(): void
    {
        $result = CheckResult::ok('database', 'Healthy.', ['latency_ms' => 2]);

        $array = $result->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('metrics', $array);
        $this->assertSame('database', $array['name']);
        $this->assertSame('ok', $array['status']);
        $this->assertSame('Healthy.', $array['message']);
        $this->assertSame(['latency_ms' => 2], $array['meta']);
        $this->assertSame([], $array['metrics']);
    }

    public function test_with_metrics_returns_new_instance(): void
    {
        $original = CheckResult::ok('database', 'Healthy.');
        $withMetrics = $original->withMetrics(['latency_ms' => 5, 'connections' => 10]);

        $this->assertNotSame($original, $withMetrics);
        $this->assertSame([], $original->metrics);
        $this->assertSame(['latency_ms' => 5, 'connections' => 10], $withMetrics->metrics);
    }

    public function test_with_metrics_preserves_other_properties(): void
    {
        $original = CheckResult::ok('database', 'Healthy.', ['host' => 'localhost']);
        $withMetrics = $original->withMetrics(['latency_ms' => 5]);

        $this->assertSame('database', $withMetrics->name);
        $this->assertSame(CheckResult::STATUS_OK, $withMetrics->status);
        $this->assertSame('Healthy.', $withMetrics->message);
        $this->assertSame(['host' => 'localhost'], $withMetrics->meta);
    }

    public function test_to_array_includes_metrics(): void
    {
        $result = CheckResult::ok('database')->withMetrics(['latency_ms' => 3]);

        $array = $result->toArray();

        $this->assertSame(['latency_ms' => 3], $array['metrics']);
    }
}
