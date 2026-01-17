<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

/**
 * Custom autoloader for MKS CMS plugins.
 *
 * This autoloader enables plugins in the plugins/ directory to be
 * loaded without requiring the user to modify composer.json.
 *
 * It reads the autoload configuration from each plugin's manifest
 * and registers the appropriate class mappings.
 */
final class PluginAutoloader
{
    /**
     * Registered namespace mappings.
     * Structure: ['Namespace\\' => '/path/to/src/']
     *
     * @var array<string, string>
     */
    private static array $namespaces = [];

    /**
     * Whether the autoloader is registered.
     */
    private static bool $registered = false;

    /**
     * Register the autoloader.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([self::class, 'loadClass'], true, true);
        self::$registered = true;
    }

    /**
     * Unregister the autoloader.
     */
    public static function unregister(): void
    {
        if (! self::$registered) {
            return;
        }

        spl_autoload_unregister([self::class, 'loadClass']);
        self::$registered = false;
        self::$namespaces = [];
    }

    /**
     * Add a namespace mapping from a plugin manifest.
     */
    public static function addFromManifest(PluginManifest $manifest): void
    {
        $autoload = $manifest->autoload();
        $basePath = $manifest->basePath();

        foreach ($autoload as $namespace => $path) {
            self::addNamespace($namespace, $basePath . '/' . ltrim($path, '/'));
        }

        // If no explicit autoload, try to infer from namespace + src/
        if (empty($autoload) && $manifest->namespace()) {
            $srcPath = $basePath . '/src';
            if (is_dir($srcPath)) {
                self::addNamespace($manifest->namespace() . '\\', $srcPath);
            }
        }
    }

    /**
     * Add a namespace mapping.
     *
     * @param  string  $namespace  The namespace prefix (with trailing backslash)
     * @param  string  $basePath  The base path for this namespace
     */
    public static function addNamespace(string $namespace, string $basePath): void
    {
        // Ensure namespace ends with backslash
        $namespace = rtrim($namespace, '\\') . '\\';

        // Ensure path ends without slash
        $basePath = rtrim($basePath, '/\\');

        self::$namespaces[$namespace] = $basePath;
    }

    /**
     * Remove a namespace mapping.
     */
    public static function removeNamespace(string $namespace): void
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        unset(self::$namespaces[$namespace]);
    }

    /**
     * Get all registered namespaces.
     *
     * @return array<string, string>
     */
    public static function getNamespaces(): array
    {
        return self::$namespaces;
    }

    /**
     * Clear all registered namespaces.
     */
    public static function clear(): void
    {
        self::$namespaces = [];
    }

    /**
     * Autoload callback - attempt to load a class.
     *
     * @param  string  $class  The fully qualified class name
     * @return bool True if class was loaded
     */
    public static function loadClass(string $class): bool
    {
        foreach (self::$namespaces as $namespace => $basePath) {
            // Check if class belongs to this namespace
            if (strpos($class, $namespace) !== 0) {
                continue;
            }

            // Get the relative class name
            $relativeClass = substr($class, strlen($namespace));

            // Convert namespace separators to directory separators
            $file = $basePath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;

                return true;
            }
        }

        return false;
    }

    /**
     * Check if a class can be loaded by this autoloader.
     */
    public static function canLoad(string $class): bool
    {
        foreach (self::$namespaces as $namespace => $basePath) {
            if (strpos($class, $namespace) !== 0) {
                continue;
            }

            $relativeClass = substr($class, strlen($namespace));
            $file = $basePath . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                return true;
            }
        }

        return false;
    }
}
