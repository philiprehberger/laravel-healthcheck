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
        $this->assertFalse($result->isCritical());
    }

    public function test_warning_factory_creates_warning_result(): void
    {
        $result = CheckResult::warning('environment', 'Debug enabled.');

        $this->assertSame(CheckResult::STATUS_WARNING, $result->status);
        $this->assertTrue($result->isWarning());
        $this->assertFalse($result->isOk());
        $this->assertFalse($result->isCritical());
    }

    public function test_critical_factory_creates_critical_result(): void
    {
        $result = CheckResult::critical('cache', 'Connection refused.');

        $this->assertSame(CheckResult::STATUS_CRITICAL, $result->status);
        $this->assertTrue($result->isCritical());
        $this->assertFalse($result->isOk());
        $this->assertFalse($result->isWarning());
    }

    public function test_defaults_are_empty_string_and_empty_array(): void
    {
        $result = CheckResult::ok('test');

        $this->assertSame('', $result->message);
        $this->assertSame([], $result->meta);
    }

    public function test_to_array_contains_all_fields(): void
    {
        $result = CheckResult::ok('database', 'Healthy.', ['latency_ms' => 2]);

        $array = $result->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertSame('database', $array['name']);
        $this->assertSame('ok', $array['status']);
        $this->assertSame('Healthy.', $array['message']);
        $this->assertSame(['latency_ms' => 2], $array['meta']);
    }
}
