<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use PhilipRehberger\Healthcheck\Checks\QueueCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class QueueCheckTest extends TestCase
{
    public function test_returns_ok_with_sync_driver(): void
    {
        // The sync driver is always available and requires no external service.
        $check = new QueueCheck('sync');
        $result = $check->check();

        $this->assertSame('ok', $result->status);
        $this->assertSame('queue', $result->name);
    }

    public function test_check_name_is_queue(): void
    {
        $this->assertSame('queue', (new QueueCheck)->name());
    }

    public function test_meta_contains_connection_and_driver(): void
    {
        $check = new QueueCheck('sync');
        $result = $check->check();

        $this->assertArrayHasKey('connection', $result->meta);
        $this->assertArrayHasKey('driver', $result->meta);
    }

    public function test_returns_critical_for_invalid_connection(): void
    {
        $check = new QueueCheck('nonexistent_queue_connection');
        $result = $check->check();

        $this->assertSame('critical', $result->status);
    }
}
