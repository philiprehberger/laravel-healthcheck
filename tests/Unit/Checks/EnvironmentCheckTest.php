<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use PhilipRehberger\Healthcheck\Checks\EnvironmentCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class EnvironmentCheckTest extends TestCase
{
    public function test_check_name_is_environment(): void
    {
        $this->assertSame('environment', (new EnvironmentCheck)->name());
    }

    public function test_returns_ok_in_testing_environment_with_debug_off(): void
    {
        config(['app.env' => 'testing', 'app.debug' => false]);

        $check = new EnvironmentCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }

    public function test_returns_ok_in_production_with_debug_off(): void
    {
        config(['app.env' => 'production', 'app.debug' => false]);

        $check = new EnvironmentCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }

    public function test_returns_warning_in_production_with_debug_on(): void
    {
        config(['app.env' => 'production', 'app.debug' => true]);

        $check = new EnvironmentCheck;
        $result = $check->check();

        $this->assertSame('warning', $result->status);
        $this->assertStringContainsString('APP_DEBUG', $result->message);
    }

    public function test_returns_ok_in_local_with_debug_on(): void
    {
        // Debug is expected in non-production environments.
        config(['app.env' => 'local', 'app.debug' => true]);

        $check = new EnvironmentCheck;
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }

    public function test_meta_contains_env_and_debug_values(): void
    {
        config(['app.env' => 'testing', 'app.debug' => false]);

        $result = (new EnvironmentCheck)->check();

        $this->assertArrayHasKey('env', $result->meta);
        $this->assertArrayHasKey('debug', $result->meta);
    }
}
