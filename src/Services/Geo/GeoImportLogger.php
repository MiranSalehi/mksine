<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Append-only geo import log (file + Laravel log channel).
 *
 * Each run writes to storage/logs/mksine-geo-import/geo-import-{runId}.log
 * so operators can tail progress while queue workers run city jobs.
 */
final class GeoImportLogger
{
    private const string LOG_DIR = 'logs/mksine-geo-import';

    public function __construct(
        private readonly string $path,
        private readonly string $runId,
        private readonly bool $initialize = true,
    ) {
        if ($this->initialize) {
            $dir = dirname($this->path);
            if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new RuntimeException("Unable to create geo import log directory: {$dir}");
            }

            $this->raw(str_repeat('=', 72));
            $this->raw(sprintf('[%s] Geo import run opened: %s', self::now(), basename($this->path)));
        }
    }

    public static function logPath(string $runId): string
    {
        return self::pathForRun($runId);
    }

    public static function open(string $runId): self
    {
        return new self(self::pathForRun($runId), $runId, initialize: true);
    }

    public static function resume(string $runId): self
    {
        return new self(self::pathForRun($runId), $runId, initialize: false);
    }

    public function runId(): string
    {
        return $this->runId;
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

    public function step(string $phase, string $detail = ''): void
    {
        $line = $detail === '' ? $phase : "{$phase} — {$detail}";
        $this->line('STEP', $line);
    }

    public function progress(string $phase, int $count, ?string $detail = null): void
    {
        $message = $detail === null
            ? "{$phase}: {$count} records processed"
            : "{$phase}: {$count} records — {$detail}";

        $this->line('PROG', $message);
    }

    private function line(string $level, string $message): void
    {
        $formatted = sprintf('[%s] %-5s %s', self::now(), $level, $message);
        $this->raw($formatted);

        Log::info('[mksine:geo-import] '.$message, [
            'run_id' => $this->runId,
            'level' => $level,
        ]);
    }

    private function raw(string $line): void
    {
        file_put_contents($this->path, $line.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function pathForRun(string $runId): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $runId) ?: 'unknown';

        return storage_path(self::LOG_DIR.'/geo-import-'.$safeId.'.log');
    }

    private static function now(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
}
