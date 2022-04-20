<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

class HealthReport
{
    public readonly HealthStatus $status;

    /**
     * @param  CheckResult[]  $checks
     */
    public function __construct(
        public readonly array $checks,
        public readonly float $durationMs,
    ) {
        $this->status = $this->resolveOverallStatus();
    }

    private function resolveOverallStatus(): HealthStatus
    {
        $hasCritical = false;
        $hasDegraded = false;

        foreach ($this->checks as $check) {
            if ($check->isCritical()) {
                $hasCritical = true;
            } elseif ($check->isDegraded() || $check->isWarning()) {
                $hasDegraded = true;
            }
        }

        if ($hasCritical) {
            return HealthStatus::Critical;
        }

        if ($hasDegraded) {
            return HealthStatus::Degraded;
        }

        return HealthStatus::Ok;
    }

    public function isHealthy(): bool
    {
        return $this->status === HealthStatus::Ok;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getMetrics(): array
    {
        $metrics = [];

        foreach ($this->checks as $check) {
            if ($check->metrics !== []) {
                $metrics[$check->name] = $check->metrics;
            }
        }

        return $metrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'duration_ms' => round($this->durationMs, 2),
            'checks' => array_map(fn (CheckResult $r) => $r->toArray(), $this->checks),
        ];
    }

    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->toArray(), $flags);

        if ($json === false) {
            return '{}';
        }

        return $json;
    }
}
