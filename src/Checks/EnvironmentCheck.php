<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Checks;

use PhilipRehberger\Healthcheck\CheckResult;
use PhilipRehberger\Healthcheck\Contracts\HealthCheck;

class EnvironmentCheck implements HealthCheck
{
    public function name(): string
    {
        return 'environment';
    }

    public function check(): CheckResult
    {
        $env = config('app.env', 'production');
        $debug = config('app.debug', false);

        if ($debug && $env === 'production') {
            return CheckResult::warning(
                $this->name(),
                'APP_DEBUG is enabled in production. This may expose sensitive information.',
                ['env' => $env, 'debug' => $debug],
            );
        }

        return CheckResult::ok(
            $this->name(),
            'Environment configuration looks healthy.',
            ['env' => $env, 'debug' => $debug],
        );
    }
}
