<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Support\Facades\File;

/**
 * Per-plugin log file writer and reader.
 * All plugin errors and disable reasons are written to storage/logs/plugins/{plugin_id}.log
 */
final class PluginLogger
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? storage_path('logs/plugins');
    }

    /**
     * Sanitize plugin ID for use as filename (no path traversal, no special chars).
     */
    public static function sanitizeId(string $pluginId): string
    {
        return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $pluginId) ?: 'plugin';
    }

    /**
     * Get the log file path for a plugin.
     */
    public function getLogPath(string $pluginId): string
    {
        $safe = self::sanitizeId($pluginId);

        return $this->basePath . '/' . $safe . '.log';
    }

    /**
     * Append a log entry for a plugin.
     *
     * @param  array<string, mixed>  $context
     */
    public function log(string $pluginId, string $level, string $message, array $context = []): void
    {
        $path = $this->getLogPath($pluginId);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Read the last N lines of a plugin's log (for admin display).
     * Returns null if file does not exist or is unreadable.
     */
    public function getLogContent(string $pluginId, int $maxLines = 500): ?string
    {
        $path = $this->getLogPath($pluginId);

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $lines = explode("\n", trim($content));
        $total = count($lines);

        if ($total <= $maxLines) {
            return $content;
        }

        $slice = array_slice($lines, -$maxLines);

        return implode("\n", $slice) . "\n\n... (" . ($total - $maxLines) . ' ' . __('lines omitted') . ')';
    }

    /**
     * Check if a plugin has a log file.
     */
    public function hasLog(string $pluginId): bool
    {
        $path = $this->getLogPath($pluginId);

        return is_file($path) && is_readable($path);
    }

    /**
     * Delete (clear) a plugin's log file.
     * Returns true if the file was deleted or did not exist.
     */
    public function clearLog(string $pluginId): bool
    {
        $path = $this->getLogPath($pluginId);

        if (! is_file($path)) {
            return true;
        }

        return @unlink($path);
    }
}
