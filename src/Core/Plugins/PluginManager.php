<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use Miran\Mksine\Support\Logging\MksineLog;

/**
 * Central coordinator for the MKS CMS plugin system.
 *
 * This class is the main entry point for all plugin operations:
 * - Discovery
 * - Installation
 * - Activation/Deactivation
 * - Booting active plugins
 * - Uninstallation
 */
final class PluginManager
{
    private PluginDiscovery $discovery;

    private PluginRegistry $registry;

    private PluginLifecycle $lifecycle;

    private PluginBootGuard $bootGuard;

    private bool $initialized = false;

    /**
     * Whether plugin PSR-4 autoload mappings are registered (no DB access).
     */
    private bool $autoloadRegistered = false;

    /**
     * Cached discovery result for a single request / Artisan run.
     *
     * @var array<string, PluginManifest>|null
     */
    private ?array $discoveredManifests = null;

    public function __construct(
        ?PluginDiscovery $discovery = null,
        ?PluginRegistry $registry = null,
        ?PluginLifecycle $lifecycle = null,
        ?PluginBootGuard $bootGuard = null
    ) {
        $this->discovery = $discovery ?? new PluginDiscovery;
        $this->registry = $registry ?? new PluginRegistry;
        $this->lifecycle = $lifecycle ?? new PluginLifecycle($this->registry);
        $this->bootGuard = $bootGuard ?? new PluginBootGuard;
    }

    /**
     * Discover plugin manifests once per process.
     *
     * @return array<string, PluginManifest>
     */
    private function getDiscoveredManifests(): array
    {
        return $this->discoveredManifests ??= $this->discovery->discover();
    }

    /**
     * Register PSR-4 for Spatie's activity log if the plugin vendors it (ZIP deploy).
     *
     * Do not require the plugin's full vendor/autoload.php: it duplicates Symfony /
     * Laravel packages and can break the host application autoload order.
     */
    private function registerPluginActivitylogAutoload(PluginManifest $manifest): void
    {
        $activitylogSrc = rtrim($manifest->basePath(), '/\\').'/vendor/spatie/laravel-activitylog/src';
        if (! is_dir($activitylogSrc)) {
            return;
        }

        $loader = new ClassLoader;
        $loader->addPsr4('Spatie\\Activitylog\\', $activitylogSrc);
        $loader->register(false);
    }

    /**
     * Register PSR-4 for rmsramos/filament-activitylog vendored under a plugin (ZIP deploy).
     *
     * Same rationale as {@see registerPluginActivitylogAutoload}: avoid loading the plugin's
     * full composer autoload during panel registration.
     */
    private function registerPluginRmsramosActivitylogAutoload(PluginManifest $manifest): void
    {
        $rmsSrc = rtrim($manifest->basePath(), '/\\').'/vendor/rmsramos/activitylog/src';
        if (! is_dir($rmsSrc)) {
            return;
        }

        $loader = new ClassLoader;
        $loader->addPsr4('Rmsramos\\Activitylog\\', $rmsSrc);
        $loader->register(false);
    }

    /**
     * Register custom autoload paths for all discovered plugins (filesystem only).
     *
     * Safe during {@see \Illuminate\Support\ServiceProvider::register()} before the
     * database connection resolver exists, so app code can reference plugin classes
     * during later provider boot (e.g. models using plugin traits).
     */
    public function registerPluginAutoload(): void
    {
        if ($this->autoloadRegistered) {
            return;
        }

        PluginAutoloader::register();

        foreach ($this->getDiscoveredManifests() as $manifest) {
            PluginAutoloader::addFromManifest($manifest);
            $this->registerPluginActivitylogAutoload($manifest);
            $this->registerPluginRmsramosActivitylogAutoload($manifest);
        }

        $this->autoloadRegistered = true;
    }

    /**
     * Initialize the plugin system.
     * This should be called early in the boot process.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->registerPluginAutoload();

        $this->registry->load($this->getDiscoveredManifests());

        $this->initialized = true;
    }

    /**
     * Boot all active plugins.
     * This should be called after initialization.
     */
    public function bootPlugins(): void
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $bootable = $this->registry->getBootablePlugins();

        foreach ($bootable as $pluginId => $manifest) {
            $this->bootPlugin($pluginId, $manifest);
        }
    }

    /**
     * Boot a single plugin.
     */
    private function bootPlugin(string $pluginId, PluginManifest $manifest): void
    {
        try {
            // Set boot guard flag
            $this->bootGuard->startBoot($pluginId);

            // Instantiate plugin
            $instance = $this->lifecycle->instantiate($manifest);

            if (! $instance) {
                throw new \RuntimeException("Failed to instantiate plugin: {$pluginId}");
            }

            // Register instance
            $this->registry->registerInstance($pluginId, $instance);

            // Call boot method
            $instance->boot();

            // Automatic route loading
            $this->loadPluginRoutes($instance, $manifest);

            // Clear boot guard flag
            $this->bootGuard->endBoot($pluginId);

            MksineLog::debug("Plugin booted successfully: {$pluginId}");

        } catch (\Throwable $e) {
            // Boot failed - log to plugin log (with trace) and disable
            $this->bootGuard->bootFailed($pluginId, $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            Log::error("Plugin boot failed: {$pluginId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update database record
            $record = $this->registry->getDbRecord($pluginId);
            if ($record) {
                $record->markBootFailed($e->getMessage());
            }
        }
    }

    /**
     * Load plugin routes if they exist.
     */
    private function loadPluginRoutes(PluginInterface $instance, PluginManifest $manifest): void
    {
        $webRoutes = $instance->webRoutesPath() ?? $manifest->webRoutesPath();
        if ($webRoutes && file_exists((string) $webRoutes)) {
            Route::middleware(['web'])->group($webRoutes);
        }

        $apiRoutes = $instance->apiRoutesPath() ?? $manifest->apiRoutesPath();
        if ($apiRoutes && file_exists((string) $apiRoutes)) {
            Route::middleware(['api'])->prefix('api')->group($apiRoutes);
        }
    }

    /**
     * Discover plugins (refresh from filesystem).
     *
     * @param  bool  $clearCache  Whether to clear discovery cache
     * @return array<string, PluginManifest>
     */
    public function discover(bool $clearCache = false): array
    {
        if ($clearCache) {
            $this->discoveredManifests = null;
            $manifests = $this->discovery->rediscover();
            $this->discoveredManifests = $manifests;
            if ($this->initialized) {
                $this->registry->load($manifests);
            }

            return $manifests;
        }

        return $this->getDiscoveredManifests();
    }

    /**
     * Install a plugin.
     */
    public function install(string $pluginId): bool
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $manifest = $this->registry->getManifest($pluginId);

        if (! $manifest) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginId}");
        }

        if ($this->registry->isInstalled($pluginId)) {
            throw new \RuntimeException("Plugin already installed: {$pluginId}");
        }

        return $this->lifecycle->install($manifest);
    }

    /**
     * Activate a plugin.
     */
    public function activate(string $pluginId): bool
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $manifest = $this->registry->getManifest($pluginId);

        if (! $manifest) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginId}");
        }

        if (! $this->registry->isInstalled($pluginId)) {
            // Auto-install if not installed
            $this->install($pluginId);
        }

        if ($this->registry->isActive($pluginId)) {
            throw new \RuntimeException("Plugin already active: {$pluginId}");
        }

        // Clear any boot failure flag
        $record = $this->registry->getDbRecord($pluginId);
        if ($record && $record->hasBootFailed()) {
            $record->clearBootFailure();
        }

        return $this->lifecycle->activate($manifest);
    }

    /**
     * Deactivate a plugin.
     */
    public function deactivate(string $pluginId): bool
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $manifest = $this->registry->getManifest($pluginId);

        if (! $manifest) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginId}");
        }

        if (! $this->registry->isActive($pluginId)) {
            throw new \RuntimeException("Plugin not active: {$pluginId}");
        }

        return $this->lifecycle->deactivate($manifest);
    }

    /**
     * Uninstall a plugin.
     *
     * @param  bool  $deleteData  Whether to delete plugin data
     */
    public function uninstall(string $pluginId, bool $deleteData = false): bool
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $manifest = $this->registry->getManifest($pluginId);

        if (! $manifest) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginId}");
        }

        if (! $this->registry->isInstalled($pluginId)) {
            throw new \RuntimeException("Plugin not installed: {$pluginId}");
        }

        // Deactivate first if active
        if ($this->registry->isActive($pluginId)) {
            $this->deactivate($pluginId);
        }

        return $this->lifecycle->uninstall($manifest, $deleteData);
    }

    /**
     * Get plugin status.
     */
    public function getStatus(string $pluginId): string
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return $this->registry->getStatus($pluginId);
    }

    /**
     * Get all plugins summary.
     */
    public function getAllPlugins(): array
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return $this->registry->getSummary();
    }

    /**
     * Get plugin manifest.
     */
    public function getManifest(string $pluginId): ?PluginManifest
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return $this->registry->getManifest($pluginId);
    }

    /**
     * Get plugin instance.
     */
    public function getInstance(string $pluginId): ?PluginInterface
    {
        return $this->registry->getInstance($pluginId);
    }

    /**
     * Get registry.
     */
    public function getRegistry(): PluginRegistry
    {
        return $this->registry;
    }

    /**
     * Run pending migrations for one installed plugin (e.g. after pulling new migration files).
     */
    public function migratePluginDatabase(string $pluginId): void
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        $manifest = $this->registry->getManifest($pluginId);

        if (! $manifest) {
            throw new \InvalidArgumentException("Plugin not found: {$pluginId}");
        }

        if (! $this->registry->isInstalled($pluginId)) {
            throw new \RuntimeException("Plugin not installed: {$pluginId}");
        }

        $this->lifecycle->runMigrations($manifest);
    }

    /**
     * Run pending migrations for every installed plugin that has a migrations directory.
     */
    public function migrateAllInstalledPlugins(): void
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        foreach ($this->registry->getManifests() as $pluginId => $manifest) {
            if (! $this->registry->isInstalled($pluginId)) {
                continue;
            }

            $migrationsPath = $manifest->migrationsPath();

            if (! $migrationsPath || ! is_dir($migrationsPath)) {
                continue;
            }

            $this->lifecycle->runMigrations($manifest);
        }
    }

    /**
     * Check for plugins that failed during previous boot.
     * This should be called early to detect and disable crashed plugins.
     */
    public function checkBootFailures(): void
    {
        $this->bootGuard->checkPreviousFailures();
    }

    /**
     * Clear discovery cache.
     */
    public function clearCache(): void
    {
        $this->discoveredManifests = null;
        $this->discovery->clearCache();
    }

    /**
     * Check if system is initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Instantiate a plugin class from a manifest without registering it in the registry.
     */
    public function instantiatePlugin(PluginManifest $manifest): ?PluginInterface
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        return $this->lifecycle->instantiate($manifest);
    }
}
