<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Health;

/**
 * One line of the system health page. $command is shown as a copyable left-to-right code line.
 */
final readonly class HealthCheck
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    public const INFO = 'info';

    public function __construct(
        public string $key,
        public string $label,
        public string $status,
        public ?string $value = null,
        public ?string $hint = null,
        public ?string $command = null,
    ) {}

    public function isProblem(): bool
    {
        return $this->status === self::WARNING || $this->status === self::ERROR;
    }

    /**
     * Filament color for the status badge.
     */
    public function color(): string
    {
        return match ($this->status) {
            self::OK => 'success',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            default => 'gray',
        };
    }
}
