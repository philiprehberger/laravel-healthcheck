<?php

declare(strict_types=1);

namespace PhilipRehberger\Healthcheck;

class CheckResult
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly string $message = '',
        public readonly array $meta = [],
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function ok(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, self::STATUS_OK, $message, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function warning(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, self::STATUS_WARNING, $message, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function critical(string $name, string $message = '', array $meta = []): self
    {
        return new self($name, self::STATUS_CRITICAL, $message, $meta);
    }

    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }

    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'message' => $this->message,
            'meta' => $this->meta,
        ];
    }
}
