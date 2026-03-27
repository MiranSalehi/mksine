<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Boot Guard for detecting and handling plugin boot failures.
 *
 * Strategy: "Per-Plugin Flag + TTL + Stale Detection"
 *
 * - One flag file per plugin: storage/framework/cache/mks_plugins/{plugin_id}.booting.json
 * - TTL: flags younger than TTL are considered "still booting" (concurrent request safe)
 * - Only disable when flag exists AND is older than TTL (clearly stale = crash)
 * - Corrupt JSON: delete file only, do NOT disable plugin
 */
final class PluginBootGuard
{
    private const DEFAULT_TTL_SECONDS = 15;

    private string $pluginsDir;

    private int $ttlSeconds;

    private PluginLogger $pluginLogger;

    public function __construct(
        ?string $pluginsDir = null,
        ?int $ttlSeconds = null,
        ?PluginLogger $pluginLogger = null
    ) {
        $this->pluginsDir = $pluginsDir ?? storage_path('framework/cache/mks_plugins');
        $this->ttlSeconds = $ttlSeconds ?? (int) (config('mksine.plugins.boot_guard_ttl', self::DEFAULT_TTL_SECONDS));
        $this->pluginLogger = $pluginLogger ?? new PluginLogger;
    }

    /**
     * Check for plugins that failed during previous boot.
     * Scans all .booting.json files; only disables if flag is stale (older than TTL).
     */
    public function checkPreviousFailures(): void
    {
        $this->ensureDirectoryExists();
        $this->removeLegacyGlobalFlag();

        $files = glob($this->pluginsDir . '/*.booting.json');

        if ($files === false) {
            return;
        }

        foreach ($files as $filePath) {
            $this->processFlagFile($filePath);
        }
    }

    /**
     * Mark that a plugin is starting to boot.
     */
    public function startBoot(string $pluginId): void
    {
        $this->ensureDirectoryExists();

        $data = [
            'plugin_id' => $pluginId,
            'started_at' => date('Y-m-d H:i:s'),
            'pid' => (string) (getmypid() ?: 0),
            'request_id' => $this->getRequestId(),
        ];

        $path = $this->getFlagPath($pluginId);
        $this->atomicWrite($path, $data);
    }

    /**
     * Mark that a plugin finished booting successfully.
     */
    public function endBoot(string $pluginId): void
    {
        $this->clearFlag($pluginId);
    }

    /**
     * Handle boot failure (called from exception handler).
     *
     * @param  array{message?: string, trace?: string}  $context
     */
    public function bootFailed(string $pluginId, string $error, array $context = []): void
    {
        Log::error('Plugin boot failed', [
            'plugin_id' => $pluginId,
            'error' => $error,
        ]);

        $this->pluginLogger->log($pluginId, 'error', 'Plugin boot failed: ' . $error, $context);

        $this->clearFlag($pluginId);
        $this->disableFailedPlugin($pluginId, $error);
    }

    /**
     * Check if any plugin is currently booting.
     */
    public function isBooting(): bool
    {
        $files = glob($this->pluginsDir . '/*.booting.json');

        return $files !== false && count($files) > 0;
    }

    /**
     * Get the first plugin ID that appears to be booting (for backward compatibility).
     */
    public function getBootingPluginId(): ?string
    {
        $files = glob($this->pluginsDir . '/*.booting.json');

        if ($files === false || empty($files)) {
            return null;
        }

        $path = $files[0];
        $content = @file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? ($data['plugin_id'] ?? null) : null;
    }

    private function getFlagPath(string $pluginId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $pluginId);

        return $this->pluginsDir . '/' . $safe . '.booting.json';
    }

    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->pluginsDir)) {
            mkdir($this->pluginsDir, 0755, true);
        }
    }

    /**
     * Remove legacy single-file flag from previous implementation.
     */
    private function removeLegacyGlobalFlag(): void
    {
        $legacy = storage_path('framework/cache/mks_plugin_booting.json');
        if (file_exists($legacy)) {
            $this->safeUnlink($legacy);
        }
    }

    private function processFlagFile(string $filePath): void
    {
        $content = @file_get_contents($filePath);

        if ($content === false) {
            $this->safeUnlink($filePath);

            return;
        }

        $data = json_decode($content, true);

        if (! is_array($data) || empty($data['plugin_id'])) {
            $this->safeUnlink($filePath);

            return;
        }

        $pluginId = $data['plugin_id'];
        $startedAt = $data['started_at'] ?? $data['timestamp'] ?? null;

        if ($startedAt === null) {
            $this->safeUnlink($filePath);

            return;
        }

        $started = strtotime($startedAt);

        if ($started === false) {
            $this->safeUnlink($filePath);

            return;
        }

        $ageSeconds = time() - $started;

        if ($ageSeconds < $this->ttlSeconds) {
            return;
        }

        $reason = 'Boot failure detected - plugin crashed during previous boot (flag stale ' . $ageSeconds . 's)';

        Log::warning('Detected plugin boot failure from previous request', [
            'plugin_id' => $pluginId,
            'started_at' => $startedAt,
            'age_seconds' => $ageSeconds,
        ]);

        $this->pluginLogger->log($pluginId, 'warning', $reason, [
            'started_at' => $startedAt,
            'age_seconds' => $ageSeconds,
        ]);

        $this->disableFailedPlugin($pluginId, $reason);
        $this->safeUnlink($filePath);
    }

    private function clearFlag(string $pluginId): void
    {
        $this->safeUnlink($this->getFlagPath($pluginId));
    }

    private function safeUnlink(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        set_error_handler(static fn () => true, E_WARNING);
        try {
            unlink($path);
        } finally {
            restore_error_handler();
        }
    }

    private function atomicWrite(string $path, array $data): void
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $temp = $path . '.' . uniqid('', true) . '.tmp';

        $written = file_put_contents($temp, $json, LOCK_EX);

        if ($written === false) {
            @unlink($temp);

            return;
        }

        if (! rename($temp, $path)) {
            @unlink($temp);
        }
    }

    private function getRequestId(): string
    {
        $val = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        return (string) $val;
    }

    private function disableFailedPlugin(string $pluginId, string $error): void
    {
        try {
            $record = PluginModel::where('plugin_id', $pluginId)->first();

            if ($record) {
                $record->update([
                    'status' => PluginModel::STATUS_INACTIVE,
                    'boot_failed' => true,
                    'boot_error' => $error,
                    'boot_failed_at' => now(),
                ]);

                $this->pluginLogger->log($pluginId, 'warning', 'Plugin auto-disabled due to boot failure', [
                    'reason' => $error,
                ]);

                Log::warning('Plugin auto-disabled due to boot failure', [
                    'plugin_id' => $pluginId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to disable plugin in database', [
                'plugin_id' => $pluginId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
