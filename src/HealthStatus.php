<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Degraded = 'degraded';
    case Critical = 'critical';
}
