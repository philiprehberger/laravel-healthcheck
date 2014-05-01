<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use PhilipRehberger\Healthcheck\HealthService;

class HealthController extends Controller
{
    public function __construct(private readonly HealthService $healthService) {}

    /**
     * Run all registered checks and return the full health report.
     *
     * Returns 200 if the overall status is "ok", 503 otherwise.
     */
    public function __invoke(): JsonResponse
    {
        $report = $this->healthService->runAll();

        return response()->json(
            $report->toArray(),
            $report->isHealthy() ? 200 : 503,
        );
    }

    /**
     * Kubernetes liveness probe.
     *
     * Always returns 200 as long as the application process is alive and
     * able to serve requests. This endpoint intentionally skips health checks.
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'message' => 'Application is alive.']);
    }

    /**
     * Kubernetes readiness probe.
     *
     * Returns 200 if the application is ready to accept traffic (all checks pass),
     * or 503 if it is not yet ready.
     */
    public function ready(): JsonResponse
    {
        $report = $this->healthService->runAll();

        return response()->json(
            $report->toArray(),
            $report->isHealthy() ? 200 : 503,
        );
    }
}
