<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

enum ConsoleProcessStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Stopped = 'stopped';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Running;
    }

    public function labelKey(): string
    {
        return match ($this) {
            self::Pending => 'status_pending',
            self::Running => 'status_running',
            self::Completed => 'status_completed',
            self::Stopped => 'status_stopped',
            self::Failed => 'status_failed',
        };
    }
}
