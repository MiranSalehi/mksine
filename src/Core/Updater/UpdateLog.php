<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use RuntimeException;

/**
 * Per-run append-only update log.
 *
 * Each update run gets its own file under storage/logs/mksine-updates/, named
 * {target}-{id}-{TS}.log, so operators can diff two runs without parsing a
 * shared file. The log records every step, warning, and error message with
 * ISO timestamps in UTC.
 *
 * The log survives even if the pipeline throws, because each append flushes
 * to disk (LOCK_EX + fflush via file_put_contents).
 */
final class UpdateLog
{
    private string $path;

    public function __construct(string $path)
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Unable to create update log directory: {$dir}");
        }

        $this->path = $path;

        $this->raw(str_repeat('=', 72));
        $this->raw(sprintf('[%s] Update log opened: %s', self::now(), basename($path)));
    }

    public static function forRun(UpdateTarget $target, string $identifier): self
    {
        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $identifier) ?: 'unknown';
        $filename = sprintf(
            '%s-%s-%s.log',
            $target->value,
            $safeId,
            date('Ymd-His')
        );

        return new self(storage_path('logs/mksine-updates/' . $filename));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function info(string $message): void
    {
        $this->line('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->line('WARN', $message);
    }

    public function error(string $message): void
    {
        $this->line('ERROR', $message);
    }

    public function step(string $name, string $detail = ''): void
    {
        $line = $detail === '' ? "STEP {$name}" : "STEP {$name} — {$detail}";
        $this->line('STEP', $line);
    }

    private function line(string $level, string $message): void
    {
        $this->raw(sprintf('[%s] %-5s %s', self::now(), $level, $message));
    }

    private function raw(string $line): void
    {
        file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function now(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
}
