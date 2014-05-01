<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use Illuminate\Support\Facades\Cache;
use PhilipRehberger\Healthcheck\Checks\CacheCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class CacheCheckTest extends TestCase
{
    public function test_returns_ok_when_cache_is_working(): void
    {
        $check = new CacheCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
        $this->assertSame('cache', $result->name);
    }

    public function test_check_name_is_cache(): void
    {
        $this->assertSame('cache', (new CacheCheck)->name());
    }

    public function test_returns_critical_when_cache_read_returns_wrong_value(): void
    {
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn('unexpected_value');
        Cache::shouldReceive('forget')->andReturn(true);

        $check = new CacheCheck;
        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('unexpected', strtolower($result->message));
    }

    public function test_returns_critical_on_cache_exception(): void
    {
        Cache::shouldReceive('put')->andThrow(new \RuntimeException('Connection refused'));

        $check = new CacheCheck;
        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('Connection refused', $result->message);
    }

    public function test_probe_key_is_cleaned_up_after_check(): void
    {
        $check = new CacheCheck;
        $check->check();

        $this->assertNull(Cache::get('_healthcheck_cache_probe'));
    }
}
