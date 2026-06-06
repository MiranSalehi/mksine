<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console;

final class AdminConsoleRunResult
{
    /**
     * @param  list<string>  $argv
     */
    public function __construct(
        public readonly string $runner,
        public readonly string $displayCommand,
        public readonly array $argv,
        public readonly int $exitCode,
        public readonly string $output,
        public readonly int $durationMs,
    ) {}

    public function succeeded(): bool
    {
        return $this->exitCode === 0;
    }
}
