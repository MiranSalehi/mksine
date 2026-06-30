<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use Illuminate\Support\Facades\Log;

/**
 * Service for discovering plugins in the filesystem.
 *
 * Discovery scans configured paths for directories containing plugin.php
 * manifest files and returns parsed manifests for each discovered plugin.
 *
 * PERFORMANCE NOTE:
 * Discovery should only run at build-time or on cache clear.
 * Results should be cached for production use.
 */
final class PluginDiscovery
{
    /**
     * Paths to scan for plugins.
     *
     * @var array<string>
     */
    private array $paths;

    /**
     * Cache file path.
     */
    private string $cachePath;

    public function __construct(?array $paths = null, ?string $cachePath = null)
    {
        $this->paths = $paths ?? $this->getDefaultPaths();
        $this->cachePath = $cachePath ?? $this->getDefaultCachePath();
    }

    /**
     * Resolved project plugins directory (honours `mksine.plugins_path`).
     */
    public static function defaultPluginsPath(): string
    {
        return base_path((string) config('mksine.plugins_path', 'plugins'));
    }

    /**
     * Get default plugin scan paths.
     */
    private function getDefaultPaths(): array
    {
        $paths = [];

        $localPlugins = self::defaultPluginsPath();
        if (is_dir($localPlugins)) {
            $paths[] = realpath($localPlugins) ?: $localPlugins;
        }

        $vendorPath = base_path('vendor');
        if (is_dir($vendorPath)) {
            $paths[] = realpath($vendorPath) ?: $vendorPath;
        }

        return $paths;
    }

    /**
     * Get default cache path.
     */
    private function getDefaultCachePath(): string
    {
        return base_path('bootstrap/cache/mks_plugins_discovery.php');
    }

    /**
     * Discover all plugins.
     *
     * @param  bool  $useCache  Whether to use cached results
     * @return array<string, PluginManifest> Array of manifests keyed by plugin ID
     */
    public function discover(bool $useCache = true): array
    {
        // Try to load from cache first
        if ($useCache && $this->hasCachedDiscovery()) {
            return $this->loadFromCache();
        }

        $manifests = [];

        foreach ($this->paths as $basePath) {
            $discovered = $this->scanPath($basePath);
            foreach ($discovered as $manifest) {
                $manifests[$manifest->id()] = $manifest;
            }
        }

        // Cache the discovery results
        $this->cacheDiscovery($manifests);

        return $manifests;
    }

    /**
     * Force re-discovery (clear cache and scan).
     *
     * @return array<string, PluginManifest>
     */
    public function rediscover(): array
    {
        $this->clearCache();

        return $this->discover(false);
    }

    /**
     * Scan a path for plugins.
     *
     * @return array<PluginManifest>
     */
    private function scanPath(string $basePath): array
    {
        $manifests = [];

        if (! is_dir($basePath)) {
            return $manifests;
        }

        if (basename($basePath) === 'vendor') {
            return $this->scanVendorPlugins($basePath);
        }

        return $this->scanLocalPlugins($basePath);
    }

    /**
     * Scan local plugins directory.
     */
    private function scanLocalPlugins(string $pluginsPath): array
    {
        $manifests = [];

        $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $manifestPath = $dir . '/plugin.php';

            if (file_exists($manifestPath)) {
                try {
                    $manifests[] = PluginManifest::fromPath($dir);
                } catch (\Exception $e) {
                    Log::warning("Failed to load plugin manifest: {$dir}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $manifests;
    }

    /**
     * Scan vendor directory for MKS plugins.
     */
    private function scanVendorPlugins(string $vendorPath): array
    {
        $manifests = [];

        // First, check installed.json for packages with type: mks-plugin
        $installedPath = $vendorPath . '/composer/installed.json';

        if (! file_exists($installedPath)) {
            return $manifests;
        }

        $installed = json_decode(file_get_contents($installedPath), true);

        // Handle both Composer 1.x and 2.x formats
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            // Check if package has mks-plugin in extra
            $extra = $package['extra'] ?? [];

            if (! isset($extra['mks-plugin'])) {
                continue;
            }

            // Build package path
            $packageName = $package['name'] ?? null;
            if (! $packageName) {
                continue;
            }

            $packagePath = $vendorPath . '/' . $packageName;
            $manifestPath = $packagePath . '/plugin.php';

            if (file_exists($manifestPath)) {
                try {
                    $manifests[] = PluginManifest::fromPath($packagePath);
                } catch (\Exception $e) {
                    Log::warning("Failed to load vendor plugin manifest: {$packagePath}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $manifests;
    }

    /**
     * Check if cached discovery exists.
     */
    public function hasCachedDiscovery(): bool
    {
        return file_exists($this->cachePath);
    }

    /**
     * Load discovery from cache.
     *
     * @return array<string, PluginManifest>
     */
    private function loadFromCache(): array
    {
        $cached = require $this->cachePath;

        if (! is_array($cached)) {
            return [];
        }

        $manifests = [];

        foreach ($cached as $pluginId => $data) {
            $basePath = $data['base_path'] ?? null;

            if ($basePath && is_dir($basePath) && file_exists($basePath . '/plugin.php')) {
                try {
                    $manifests[$pluginId] = PluginManifest::fromPath($basePath);
                } catch (\Exception $e) {
                    // Plugin may have been removed or corrupted
                    Log::warning("Cached plugin no longer valid: {$pluginId}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $manifests;
    }

    /**
     * Cache discovery results.
     *
     * @param  array<string, PluginManifest>  $manifests
     */
    private function cacheDiscovery(array $manifests): void
    {
        $cacheData = [];

        foreach ($manifests as $pluginId => $manifest) {
            $cacheData[$pluginId] = $manifest->toArray();
        }

        $content = '<?php return ' . var_export($cacheData, true) . ';';

        $cacheDir = dirname($this->cachePath);
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($this->cachePath, $content);
    }

    /**
     * Clear discovery cache.
     */
    public function clearCache(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Get configured scan paths.
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
