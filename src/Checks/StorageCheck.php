<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\Storage;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class StorageCheck implements HealthCheck
{
    private const TEST_FILE = '_healthcheck_storage_probe.txt';

    private const TEST_CONTENT = 'ok';

    public function __construct(private readonly string $disk = 'default') {}

    public function name(): string
    {
        return 'storage';
    }

    public function check(): CheckResult
    {
        try {
            $disk = $this->disk === 'default'
                ? Storage::disk()
                : Storage::disk($this->disk);

            $diskName = $this->disk === 'default'
                ? config('filesystems.default', 'local')
                : $this->disk;

            try {
                $disk->put(self::TEST_FILE, self::TEST_CONTENT);
                $content = $disk->get(self::TEST_FILE);
            } finally {
                $disk->delete(self::TEST_FILE);
            }

            if ($content !== self::TEST_CONTENT) {
                return CheckResult::critical(
                    $this->name(),
                    'Storage read returned unexpected content.',
                    ['disk' => $diskName],
                );
            }

            return CheckResult::ok(
                $this->name(),
                'Storage is healthy.',
                ['disk' => $diskName],
            );
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'Storage check failed: '.$e->getMessage(),
                ['disk' => $this->disk],
            );
        }
    }
}
