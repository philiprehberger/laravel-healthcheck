<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Contracts;

use PhilipRehberger\Healthcheck\CheckResult;

interface HealthCheck
{
    /**
     * Return the unique name of this health check.
     */
    public function name(): string;

    /**
     * Execute the health check and return the result.
     */
    public function check(): CheckResult;
}
