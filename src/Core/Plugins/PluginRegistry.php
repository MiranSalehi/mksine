<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Registry of discovered and loaded plugins.
 *
 * This class holds:
 * - Discovered manifests (from filesystem)
 * - Database state (from mks_plugins table)
 * - Instantiated plugin objects (for active plugins)
 *
 * It provides a unified view of all plugins and their states.
 */
final class PluginRegistry
{
    /**
     * Discovered manifests.
     *
     * @var array<string, PluginManifest>
     */
    private array $manifests = [];

    /**
     * Database records.
     *
     * @var array<string, PluginModel>
     */
    private array $dbRecords = [];

    /**
     * Instantiated plugin objects.
     *
     * @var array<string, PluginInterface>
     */
    private array $instances = [];

    /**
     * Whether registry is loaded.
     */
    private bool $loaded = false;

    /**
     * Load registry from discovery and database.
     */
    public function load(array $manifests): void
    {
        $this->manifests = $manifests;
        // Always replace DB cache on load; otherwise removed plugins (or a failed first
        // hydration) can leave stale rows in memory and confuse isInstalled/getStatus.
        $this->dbRecords = [];
        $this->loadDatabaseRecords();
        $this->loaded = true;
    }

    /**
     * Load database records for all discovered plugins.
     */
    private function loadDatabaseRecords(): void
    {
        if (! function_exists('app')) {
            return;
        }

        try {
            if (! app()->bound('db')) {
                return;
            }

            $pluginIds = array_keys($this->manifests);

            if (empty($pluginIds)) {
                return;
            }

            $records = PluginModel::whereIn('plugin_id', $pluginIds)->get();

            foreach ($records as $record) {
                $this->dbRecords[$record->plugin_id] = $record;
            }
        } catch (\Throwable $e) {
            // Database may not be ready (e.g., during migrations, or PHPUnit without Laravel)
            // This is fine, we just won't have DB state
        }
    }

    /**
     * Check if registry is loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get all discovered manifests.
     *
     * @return array<string, PluginManifest>
     */
    public function getManifests(): array
    {
        return $this->manifests;
    }

    /**
     * Get manifest for a specific plugin.
     */
    public function getManifest(string $pluginId): ?PluginManifest
    {
        return $this->manifests[$pluginId] ?? null;
    }

    /**
     * Check if plugin is discovered (exists in filesystem).
     */
    public function isDiscovered(string $pluginId): bool
    {
        return isset($this->manifests[$pluginId]);
    }

    /**
     * Get database record for a plugin.
     */
    public function getDbRecord(string $pluginId): ?PluginModel
    {
        return $this->dbRecords[$pluginId] ?? null;
    }

    /**
     * Check if plugin is installed (has DB record).
     */
    public function isInstalled(string $pluginId): bool
    {
        return isset($this->dbRecords[$pluginId]);
    }

    /**
     * Check if plugin is active.
     */
    public function isActive(string $pluginId): bool
    {
        $record = $this->getDbRecord($pluginId);

        return $record && $record->isActive();
    }

    /**
     * Check if plugin boot failed.
     */
    public function hasBootFailed(string $pluginId): bool
    {
        $record = $this->getDbRecord($pluginId);

        return $record && $record->hasBootFailed();
    }

    /**
     * Get plugin status.
     *
     * @return string 'not_discovered' | 'not_installed' | 'installed' | 'active' | 'inactive' | 'boot_failed'
     */
    public function getStatus(string $pluginId): string
    {
        if (! $this->isDiscovered($pluginId)) {
            return 'not_discovered';
        }

        $record = $this->getDbRecord($pluginId);

        if (! $record) {
            return 'not_installed';
        }

        if ($record->hasBootFailed()) {
            return 'boot_failed';
        }

        return $record->status;
    }

    /**
     * Register a plugin instance.
     */
    public function registerInstance(string $pluginId, PluginInterface $instance): void
    {
        $this->instances[$pluginId] = $instance;
    }

    /**
     * Get plugin instance.
     */
    public function getInstance(string $pluginId): ?PluginInterface
    {
        return $this->instances[$pluginId] ?? null;
    }

    /**
     * Check if plugin instance exists.
     */
    public function hasInstance(string $pluginId): bool
    {
        return isset($this->instances[$pluginId]);
    }

    /**
     * Get all active plugins that should be booted.
     *
     * @return array<string, PluginManifest>
     */
    public function getBootablePlugins(): array
    {
        $bootable = [];

        foreach ($this->manifests as $pluginId => $manifest) {
            $record = $this->getDbRecord($pluginId);

            if ($record && $record->isActive() && ! $record->hasBootFailed()) {
                $bootable[$pluginId] = $manifest;
            }
        }

        return $bootable;
    }

    /**
     * Get all plugin IDs.
     *
     * @return array<string>
     */
    public function getAllPluginIds(): array
    {
        return array_keys($this->manifests);
    }

    /**
     * Update database record cache.
     */
    public function refreshDbRecord(string $pluginId): void
    {
        try {
            if (! function_exists('app') || ! app()->bound('db')) {
                return;
            }

            $record = PluginModel::where('plugin_id', $pluginId)->first();

            if ($record) {
                $this->dbRecords[$pluginId] = $record;
            } else {
                unset($this->dbRecords[$pluginId]);
            }
        } catch (\Throwable $e) {
            // Ignore database errors
        }
    }

    /**
     * Add a DB record to the registry.
     */
    public function setDbRecord(string $pluginId, PluginModel $record): void
    {
        $this->dbRecords[$pluginId] = $record;
    }

    /**
     * Get summary of all plugins.
     *
     * @return array<array{id: string, name: string, version: string, status: string}>
     */
    public function getSummary(): array
    {
        $summary = [];

        foreach ($this->manifests as $pluginId => $manifest) {
            $summary[] = [
                'id' => $pluginId,
                'name' => $manifest->name(),
                'version' => $manifest->version(),
                'status' => $this->getStatus($pluginId),
                'author' => $manifest->author(),
                'description' => $manifest->description(),
            ];
        }

        return $summary;
    }

    /**
     * Clear the registry.
     */
    public function clear(): void
    {
        $this->manifests = [];
        $this->dbRecords = [];
        $this->instances = [];
        $this->loaded = false;
    }
}
