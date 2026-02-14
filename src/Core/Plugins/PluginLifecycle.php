<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Handles plugin lifecycle operations: install, activate, deactivate, uninstall.
 */
final class PluginLifecycle
{
    private PluginRegistry $registry;

    private PluginLogger $pluginLogger;

    public function __construct(PluginRegistry $registry, ?PluginLogger $pluginLogger = null)
    {
        $this->registry = $registry;
        $this->pluginLogger = $pluginLogger ?? new PluginLogger;
    }

    /**
     * Install a plugin.
     */
    public function install(PluginManifest $manifest): bool
    {
        $pluginId = $manifest->id();

        Log::info("Installing plugin: {$pluginId}");

        try {
            // Create database record
            $record = PluginModel::create([
                'plugin_id' => $pluginId,
                'status' => PluginModel::STATUS_INSTALLED,
                'installed_at' => now(),
            ]);

            // Update registry
            $this->registry->setDbRecord($pluginId, $record);

            // Instantiate plugin to run install hook
            $instance = $this->instantiate($manifest);

            if ($instance) {
                // Run migrations
                $this->runMigrations($manifest);

                // Call plugin's install method
                $instance->install();
            }

            Log::info("Plugin installed successfully: {$pluginId}");

            return true;

        } catch (\Exception $e) {
            Log::error("Plugin installation failed: {$pluginId}", [
                'error' => $e->getMessage(),
            ]);

            // Cleanup on failure
            PluginModel::where('plugin_id', $pluginId)->delete();

            throw $e;
        }
    }

    /**
     * Activate a plugin.
     */
    public function activate(PluginManifest $manifest): bool
    {
        $pluginId = $manifest->id();

        Log::info("Activating plugin: {$pluginId}");

        try {
            $record = $this->registry->getDbRecord($pluginId);

            if (! $record) {
                throw new \RuntimeException("Plugin not installed: {$pluginId}");
            }

            // Update status
            $record->update([
                'status' => PluginModel::STATUS_ACTIVE,
                'activated_at' => now(),
                'deactivated_at' => null,
            ]);

            // Refresh registry
            $this->registry->refreshDbRecord($pluginId);

            // Call plugin's activate method
            $instance = $this->instantiate($manifest);
            if ($instance) {
                $instance->activate();
            }

            Log::info("Plugin activated successfully: {$pluginId}");

            return true;

        } catch (\Exception $e) {
            Log::error("Plugin activation failed: {$pluginId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Deactivate a plugin.
     */
    public function deactivate(PluginManifest $manifest): bool
    {
        $pluginId = $manifest->id();

        Log::info("Deactivating plugin: {$pluginId}");

        try {
            $record = $this->registry->getDbRecord($pluginId);

            if (! $record) {
                throw new \RuntimeException("Plugin not installed: {$pluginId}");
            }

            // Call plugin's deactivate method first
            $instance = $this->registry->getInstance($pluginId);
            if (! $instance) {
                $instance = $this->instantiate($manifest);
            }

            if ($instance) {
                $instance->deactivate();
            }

            // Update status
            $record->update([
                'status' => PluginModel::STATUS_INACTIVE,
                'deactivated_at' => now(),
            ]);

            // Refresh registry
            $this->registry->refreshDbRecord($pluginId);

            $this->pluginLogger->log($pluginId, 'info', 'Plugin deactivated', ['by' => 'user']);

            Log::info("Plugin deactivated successfully: {$pluginId}");

            return true;

        } catch (\Exception $e) {
            $this->pluginLogger->log($pluginId, 'error', 'Plugin deactivation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error("Plugin deactivation failed: {$pluginId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Uninstall a plugin.
     */
    public function uninstall(PluginManifest $manifest, bool $deleteData = false): bool
    {
        $pluginId = $manifest->id();

        Log::info("Uninstalling plugin: {$pluginId}", ['deleteData' => $deleteData]);

        try {
            // Call plugin's uninstall method
            $instance = $this->instantiate($manifest);
            if ($instance) {
                $instance->uninstall($deleteData);
            }

            // Delete database record
            PluginModel::where('plugin_id', $pluginId)->delete();

            // Refresh registry
            $this->registry->refreshDbRecord($pluginId);

            Log::info("Plugin uninstalled successfully: {$pluginId}");

            return true;

        } catch (\Exception $e) {
            Log::error("Plugin uninstallation failed: {$pluginId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Instantiate a plugin class.
     */
    public function instantiate(PluginManifest $manifest): ?PluginInterface
    {
        $pluginClass = $manifest->pluginClass();

        if (! $pluginClass) {
            // Try to infer from namespace
            $namespace = $manifest->namespace();
            if ($namespace) {
                // Convention: {Namespace}\{PluginName}Plugin
                $parts = explode('-', $manifest->id());
                $className = implode('', array_map('ucfirst', $parts)) . 'Plugin';
                $pluginClass = rtrim($namespace, '\\') . '\\' . $className;
            }
        }

        if (! $pluginClass) {
            Log::warning("No plugin class found for: {$manifest->id()}");

            return null;
        }

        if (! class_exists($pluginClass)) {
            Log::warning("Plugin class does not exist: {$pluginClass}");

            return null;
        }

        $instance = app($pluginClass);

        if (! $instance instanceof PluginInterface) {
            Log::warning("Plugin class does not implement PluginInterface: {$pluginClass}");

            return null;
        }

        return $instance;
    }

    /**
     * Run plugin migrations.
     */
    private function runMigrations(PluginManifest $manifest): void
    {
        $migrationsPath = $manifest->migrationsPath();

        if (! $migrationsPath || ! is_dir($migrationsPath)) {
            return;
        }

        Log::info("Running migrations for plugin: {$manifest->id()}", [
            'path' => $migrationsPath,
        ]);

        try {
            Artisan::call('migrate', [
                '--path' => str_replace(base_path() . '/', '', $migrationsPath),
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Migration failed for plugin: {$manifest->id()}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Rollback plugin migrations.
     */
    public function rollbackMigrations(PluginManifest $manifest): void
    {
        $migrationsPath = $manifest->migrationsPath();

        if (! $migrationsPath || ! is_dir($migrationsPath)) {
            return;
        }

        Log::info("Rolling back migrations for plugin: {$manifest->id()}", [
            'path' => $migrationsPath,
        ]);

        try {
            Artisan::call('migrate:rollback', [
                '--path' => str_replace(base_path() . '/', '', $migrationsPath),
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Migration rollback failed for plugin: {$manifest->id()}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
