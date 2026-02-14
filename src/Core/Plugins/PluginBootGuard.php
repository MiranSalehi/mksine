<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Boot Guard for detecting and handling plugin boot failures.
 *
 * Strategy: "Boot Flag + Next-Request Disable"
 *
 * 1. Before booting a plugin, we write a flag to a file
 * 2. After successful boot, we remove the flag
 * 3. If the application crashes (fatal error), the flag remains
 * 4. On next request, we check for leftover flags and disable those plugins
 *
 * All disable reasons and errors are also written to the plugin's dedicated log file.
 */
final class PluginBootGuard
{
    /**
     * Path to boot flag file.
     */
    private string $flagPath;

    private PluginLogger $pluginLogger;

    public function __construct(?string $flagPath = null, ?PluginLogger $pluginLogger = null)
    {
        $this->flagPath = $flagPath ?? $this->getDefaultFlagPath();
        $this->pluginLogger = $pluginLogger ?? new PluginLogger;
    }

    /**
     * Get default flag file path.
     */
    private function getDefaultFlagPath(): string
    {
        return storage_path('framework/cache/mks_plugin_booting.json');
    }

    /**
     * Check for plugins that failed during previous boot.
     * This should be called very early in the boot process.
     */
    public function checkPreviousFailures(): void
    {
        if (! file_exists($this->flagPath)) {
            return;
        }

        try {
            $content = file_get_contents($this->flagPath);
            $data = json_decode($content, true);

            if (! is_array($data) || empty($data['plugin_id'])) {
                // Invalid flag file, remove it
                $this->clearFlag();

                return;
            }

            $pluginId = $data['plugin_id'];
            $timestamp = $data['timestamp'] ?? null;
            $reason = 'Boot failure detected - plugin crashed during previous boot';

            Log::warning('Detected plugin boot failure from previous request', [
                'plugin_id' => $pluginId,
                'timestamp' => $timestamp,
            ]);

            $this->pluginLogger->log($pluginId, 'warning', $reason, ['timestamp' => $timestamp]);

            // Disable the plugin in database
            $this->disableFailedPlugin($pluginId, $reason);

            // Clear the flag
            $this->clearFlag();

        } catch (\Exception $e) {
            Log::error('Error checking boot failures', [
                'error' => $e->getMessage(),
            ]);
            $this->clearFlag();
        }
    }

    /**
     * Mark that a plugin is starting to boot.
     */
    public function startBoot(string $pluginId): void
    {
        $data = [
            'plugin_id' => $pluginId,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $dir = dirname($this->flagPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->flagPath, json_encode($data));
    }

    /**
     * Mark that a plugin finished booting successfully.
     */
    public function endBoot(string $pluginId): void
    {
        // Only clear if this plugin is the one that was booting
        if (file_exists($this->flagPath)) {
            $content = file_get_contents($this->flagPath);
            $data = json_decode($content, true);

            if (is_array($data) && ($data['plugin_id'] ?? null) === $pluginId) {
                $this->clearFlag();
            }
        }
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

        // Clear the boot flag
        $this->clearFlag();

        // Disable the plugin
        $this->disableFailedPlugin($pluginId, $error);
    }

    /**
     * Disable a plugin that failed to boot.
     */
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
            // Database might not be available, log and continue
            Log::error('Failed to disable plugin in database', [
                'plugin_id' => $pluginId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear the boot flag file.
     */
    private function clearFlag(): void
    {
        if (file_exists($this->flagPath)) {
            unlink($this->flagPath);
        }
    }

    /**
     * Check if a plugin is currently being booted.
     */
    public function isBooting(): bool
    {
        return file_exists($this->flagPath);
    }

    /**
     * Get the plugin ID that is currently booting.
     */
    public function getBootingPluginId(): ?string
    {
        if (! file_exists($this->flagPath)) {
            return null;
        }

        $content = file_get_contents($this->flagPath);
        $data = json_decode($content, true);

        return $data['plugin_id'] ?? null;
    }
}
