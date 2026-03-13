<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use Illuminate\Support\Facades\Redis;
use PhilipRehberger\Healthcheck\Checks\RedisCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;
use Predis\Client;

class RedisCheckTest extends TestCase
{
    public function test_check_name_is_redis(): void
    {
        $this->assertSame('redis', (new RedisCheck)->name());
    }

    public function test_returns_warning_when_redis_extension_not_available(): void
    {
        // When neither the redis extension nor predis is installed, the check
        // should return a warning rather than a critical.
        if (extension_loaded('redis') || class_exists(Client::class)) {
            $this->markTestSkipped('Redis extension or Predis is available; cannot test unavailability.');
        }

        $check = new RedisCheck;
        $result = $check->check();

        $this->assertSame('warning', $result->status);
    }

    public function test_returns_critical_when_ping_fails(): void
    {
        if (! extension_loaded('redis') && ! class_exists(Client::class)) {
            $this->markTestSkipped('Redis extension and Predis are not available.');
        }

        $mockConnection = \Mockery::mock();
        $mockConnection->shouldReceive('ping')->andThrow(new \RuntimeException('Connection refused'));

        Redis::shouldReceive('connection')->with('default')->andReturn($mockConnection);

        $check = new RedisCheck;
        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('Connection refused', $result->message);
    }

    public function test_returns_ok_when_ping_returns_true(): void
    {
        if (! extension_loaded('redis') && ! class_exists(Client::class)) {
            $this->markTestSkipped('Redis extension and Predis are not available.');
        }

        $mockConnection = \Mockery::mock();
        $mockConnection->shouldReceive('ping')->andReturn(true);

        Redis::shouldReceive('connection')->with('default')->andReturn($mockConnection);

        $check = new RedisCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }

    public function test_returns_ok_when_ping_returns_pong_string(): void
    {
        if (! extension_loaded('redis') && ! class_exists(Client::class)) {
            $this->markTestSkipped('Redis extension and Predis are not available.');
        }

        $mockConnection = \Mockery::mock();
        $mockConnection->shouldReceive('ping')->andReturn('+PONG');

        Redis::shouldReceive('connection')->with('default')->andReturn($mockConnection);

        $check = new RedisCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }
}
