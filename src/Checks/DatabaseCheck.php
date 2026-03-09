<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use Illuminate\Support\Facades\DB;
use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;
use Throwable;

class DatabaseCheck implements HealthCheck
{
    public function __construct(private readonly string $connection = 'default') {}

    public function name(): string
    {
        return 'database';
    }

    public function check(): CheckResult
    {
        try {
            $conn = $this->connection === 'default'
                ? DB::connection()
                : DB::connection($this->connection);

            $conn->getPdo();

            return CheckResult::ok(
                $this->name(),
                'Database connection is healthy.',
                ['connection' => $conn->getName()],
            );
        } catch (Throwable $e) {
            return CheckResult::critical(
                $this->name(),
                'Database connection failed: '.$e->getMessage(),
                ['connection' => $this->connection],
            );
        }
    }
}
