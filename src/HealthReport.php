<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

class HealthReport
{
    public readonly string $status;

    /**
     * @param  CheckResult[]  $checks
     */
    public function __construct(
        public readonly array $checks,
        public readonly float $durationMs,
    ) {
        $this->status = $this->resolveOverallStatus();
    }

    private function resolveOverallStatus(): string
    {
        $hasCritical = false;
        $hasWarning = false;

        foreach ($this->checks as $check) {
            if ($check->isCritical()) {
                $hasCritical = true;
            } elseif ($check->isWarning()) {
                $hasWarning = true;
            }
        }

        if ($hasCritical) {
            return CheckResult::STATUS_CRITICAL;
        }

        if ($hasWarning) {
            return CheckResult::STATUS_WARNING;
        }

        return CheckResult::STATUS_OK;
    }

    public function isHealthy(): bool
    {
        return $this->status === CheckResult::STATUS_OK;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
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
