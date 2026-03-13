<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Tests\Unit\Checks;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PhilipRehberger\Healthcheck\Checks\HttpCheck;
use PhilipRehberger\Healthcheck\Tests\TestCase;

class HttpCheckTest extends TestCase
{
    public function test_check_name_defaults_to_http(): void
    {
        $this->assertSame('http', (new HttpCheck('https://example.com'))->name());
    }

    public function test_custom_check_name_is_used(): void
    {
        $check = new HttpCheck('https://example.com', 5, 200, 'external_api');

        $this->assertSame('external_api', $check->name());
    }

    public function test_returns_ok_when_expected_status_received(): void
    {
        Http::fake(['https://example.com' => Http::response('OK', 200)]);

        $check = new HttpCheck('https://example.com');
        $result = $check->check();

        $this->assertSame('ok', $result->status);
        $this->assertStringContainsString('200', $result->message);
    }

    public function test_returns_critical_when_unexpected_status_received(): void
    {
        Http::fake(['https://example.com' => Http::response('Not Found', 404)]);

        $check = new HttpCheck('https://example.com');
        $result = $check->check();

        $this->assertSame('critical', $result->status);
        $this->assertStringContainsString('404', $result->message);
    }

    public function test_returns_critical_on_connection_exception(): void
    {
        Http::fake(['https://down.example.com' => function () {
            throw new ConnectionException('Connection refused');
        }]);

        $check = new HttpCheck('https://down.example.com');
        $result = $check->check();

        $this->assertSame('critical', $result->status);
    }

    public function test_accepts_custom_expected_status(): void
    {
        Http::fake(['https://example.com/redirect' => Http::response('', 301)]);

        $check = new HttpCheck('https://example.com/redirect', 5, 301);
        $result = $check->check();

        $this->assertSame('ok', $result->status);
    }

    public function test_meta_contains_url_and_status(): void
    {
        Http::fake(['https://example.com' => Http::response('OK', 200)]);

        $result = (new HttpCheck('https://example.com'))->check();

        $this->assertArrayHasKey('url', $result->meta);
        $this->assertArrayHasKey('status', $result->meta);
        $this->assertSame('https://example.com', $result->meta['url']);
        $this->assertSame(200, $result->meta['status']);
    }
}
