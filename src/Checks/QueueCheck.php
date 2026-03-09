<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\Queue;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class QueueCheck implements HealthCheck
{
    public function __construct(private readonly ?string $connection = null) {}

    public function name(): string
    {
        return 'queue';
    }

    public function check(): CheckResult
    {
        try {
            $connectionName = $this->connection ?? config('queue.default', 'sync');

            $connection = Queue::connection($this->connection);

            // Attempt to get the queue size as a connectivity probe.
            // Not all drivers support size(), so we just verify the connection resolves.
            $driver = get_class($connection);

            return CheckResult::ok(
                $this->name(),
                'Queue connection is healthy.',
                [
                    'connection' => $connectionName,
                    'driver' => class_basename($driver),
                ],
            );
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'Queue check failed: '.$e->getMessage(),
                ['connection' => $this->connection ?? config('queue.default', 'sync')],
            );
        }
    }
}
