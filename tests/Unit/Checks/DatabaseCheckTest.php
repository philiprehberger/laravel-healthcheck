<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use PhilipRehberger\Healthcheck\Checks\DatabaseCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class DatabaseCheckTest extends TestCase
{
    public function test_returns_ok_when_database_is_connected(): void
    {
        $check = new DatabaseCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
        $this->assertSame('database', $result->name);
    }

    public function test_check_name_is_database(): void
    {
        $this->assertSame('database', (new DatabaseCheck)->name());
    }

    public function test_returns_critical_when_connection_fails(): void
    {
        $check = new DatabaseCheck('nonexistent_connection');

        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('failed', strtolower($result->message));
    }

    public function test_meta_contains_connection_name(): void
    {
        $check = new DatabaseCheck;
        $result = $check->check();

        $this->assertArrayHasKey('connection', $result->meta);
    }
}
