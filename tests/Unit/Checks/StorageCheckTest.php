<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use Illuminate\Support\Facades\Storage;
use PhilipRehberger\Healthcheck\Checks\StorageCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class StorageCheckTest extends TestCase
{
    public function test_returns_ok_when_storage_is_working(): void
    {
        Storage::fake('local');

        $check = new StorageCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
        $this->assertSame('storage', $result->name);
    }

    public function test_check_name_is_storage(): void
    {
        $this->assertSame('storage', (new StorageCheck)->name());
    }

    public function test_returns_critical_on_storage_exception(): void
    {
        Storage::shouldReceive('disk')->andThrow(new \RuntimeException('Disk not found'));

        $check = new StorageCheck;
        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('Disk not found', $result->message);
    }

    public function test_meta_contains_disk_name(): void
    {
        Storage::fake('local');

        $check = new StorageCheck;
        $result = $check->check();

        $this->assertArrayHasKey('disk', $result->meta);
    }

    public function test_probe_file_is_removed_after_check(): void
    {
        Storage::fake('local');

        $check = new StorageCheck;
        $check->check();

        Storage::disk('local')->assertMissing('_healthcheck_storage_probe.txt');
    }
}
