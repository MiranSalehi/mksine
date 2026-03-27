<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Plugins;

use InvalidArgumentException;

/**
 * Represents the parsed manifest (plugin.php) of a plugin.
 *
 * The manifest is the source of truth for plugin metadata.
 * It contains information like name, version, dependencies, hooks, etc.
 */
final class PluginManifest
{
    private array $data;

    private string $path;

    private string $basePath;

    private function __construct(array $data, string $path, string $basePath)
    {
        $this->data = $data;
        $this->path = $path;
        $this->basePath = $basePath;
    }

    /**
     * Load manifest from plugin.php file.
     *
     * @param  string  $pluginPath  Path to the plugin directory
     *
     * @throws InvalidArgumentException If manifest is invalid
     */
    public static function fromPath(string $pluginPath): self
    {
        $manifestPath = rtrim($pluginPath, '/') . '/plugin.php';

        if (! file_exists($manifestPath)) {
            throw new InvalidArgumentException(
                "Plugin manifest not found: {$manifestPath}"
            );
        }

        $data = require $manifestPath;

        if (! is_array($data)) {
            throw new InvalidArgumentException(
                "Plugin manifest must return an array: {$manifestPath}"
            );
        }

        $instance = new self($data, $manifestPath, $pluginPath);
        $instance->validate();

        return $instance;
    }

    /**
     * Validate manifest has required fields.
     */
    private function validate(): void
    {
        $required = ['id', 'name', 'version'];

        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                throw new InvalidArgumentException(
                    "Plugin manifest missing required field '{$field}': {$this->path}"
                );
            }
        }

        // Validate plugin ID format (lowercase, alphanumeric, hyphens)
        if (! preg_match('/^[a-z0-9\-]+$/', $this->data['id'])) {
            throw new InvalidArgumentException(
                "Plugin ID must be lowercase alphanumeric with hyphens only: {$this->data['id']}"
            );
        }
    }

    /**
     * Get the plugin ID.
     */
    public function id(): string
    {
        return $this->data['id'];
    }

    /**
     * Get the plugin name.
     */
    public function name(): string
    {
        return $this->data['name'];
    }

    /**
     * Get the plugin version.
     */
    public function version(): string
    {
        return $this->data['version'];
    }

    /**
     * Get the plugin description.
     */
    public function description(): ?string
    {
        return $this->data['description'] ?? null;
    }

    /**
     * Get the plugin author.
     */
    public function author(): ?string
    {
        return $this->data['author'] ?? null;
    }

    /**
     * Get plugin dependencies.
     * Returns array of ['package-name' => 'version-constraint']
     */
    public function requires(): array
    {
        return $this->data['requires'] ?? [];
    }

    /**
     * Get public hooks exposed by this plugin.
     */
    public function publicHooks(): array
    {
        return $this->data['hooks']['public'] ?? [];
    }

    /**
     * Get private hooks (internal to this plugin).
     */
    public function privateHooks(): array
    {
        return $this->data['hooks']['private'] ?? [];
    }

    /**
     * Get the main plugin class.
     */
    public function pluginClass(): ?string
    {
        return $this->data['plugin_class'] ?? null;
    }

    /**
     * Get the plugin namespace.
     */
    public function namespace(): ?string
    {
        return $this->data['namespace'] ?? null;
    }

    /**
     * Get the autoload configuration.
     * Returns PSR-4 style mapping: ['Namespace\\' => 'src/']
     */
    public function autoload(): array
    {
        return $this->data['autoload'] ?? [];
    }

    /**
     * Get public API facade class.
     */
    public function publicApiFacade(): ?string
    {
        return $this->data['public_api']['facade'] ?? null;
    }

    /**
     * Get the path to plugin.php file.
     */
    public function manifestPath(): string
    {
        return $this->path;
    }

    /**
     * Get the plugin base directory path.
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get migrations path (if exists).
     */
    public function migrationsPath(): ?string
    {
        $path = $this->basePath . '/database/migrations';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get config path (if exists).
     */
    public function configPath(): ?string
    {
        $path = $this->basePath . '/config';

        return is_dir($path) ? $path : null;
    }

    /**
     * Path to publishes/ — JSON presets for {@see \Miran\Mksine\Core\Plugins\Publishing\PluginVendorPublishRunner}.
     */
    public function publishesPath(): string
    {
        return $this->basePath . '/publishes';
    }

    /**
     * Get web routes path (if exists).
     */
    public function webRoutesPath(): ?string
    {
        $path = $this->basePath . '/routes/web.php';

        return file_exists($path) ? $path : null;
    }

    /**
     * Get API routes path (if exists).
     */
    public function apiRoutesPath(): ?string
    {
        $path = $this->basePath . '/routes/api.php';

        return file_exists($path) ? $path : null;
    }

    /**
     * Get views path (if exists).
     */
    public function viewsPath(): ?string
    {
        $path = $this->basePath . '/resources/views';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get translations path (if exists).
     */
    public function translationsPath(): ?string
    {
        $path = $this->basePath . '/resources/lang';
        if (is_dir($path)) {
            return $path;
        }
        $path = $this->basePath . '/lang';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get Filament resources path (if exists).
     */
    public function filamentResourcesPath(): ?string
    {
        $path = $this->basePath . '/src/Filament/Resources';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get Filament pages path (if exists).
     */
    public function filamentPagesPath(): ?string
    {
        $path = $this->basePath . '/src/Filament/Pages';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get Filament widgets path (if exists).
     */
    public function filamentWidgetsPath(): ?string
    {
        $path = $this->basePath . '/src/Filament/Widgets';

        return is_dir($path) ? $path : null;
    }

    /**
     * Get the public directory path for this plugin's published assets.
     * Assets are served from: public/plugins/{id}/
     */
    public function publicPath(): string
    {
        return public_path('plugins/' . $this->id());
    }

    /**
     * Get compiled CSS path inside the plugin (source, not yet published).
     * Expected at: {plugin}/resources/dist/app.css
     */
    public function distCssPath(): ?string
    {
        $path = $this->basePath . '/resources/dist/app.css';

        return file_exists($path) ? $path : null;
    }

    /**
     * Get compiled JS path inside the plugin (source, not yet published).
     * Expected at: {plugin}/resources/dist/app.js
     */
    public function distJsPath(): ?string
    {
        $path = $this->basePath . '/resources/dist/app.js';

        return file_exists($path) ? $path : null;
    }

    /**
     * Get CSS URL for the published asset (public/plugins/{id}/app.css).
     * Returns null if not yet published.
     */
    public function publishedCssUrl(): ?string
    {
        $publicFile = $this->publicPath() . '/app.css';

        return file_exists($publicFile) ? asset('plugins/' . $this->id() . '/app.css') : null;
    }

    /**
     * Get JS URL for the published asset (public/plugins/{id}/app.js).
     * Returns null if not yet published.
     */
    public function publishedJsUrl(): ?string
    {
        $publicFile = $this->publicPath() . '/app.js';

        return file_exists($publicFile) ? asset('plugins/' . $this->id() . '/app.js') : null;
    }

    /**
     * Check whether the plugin's dist/ folder has been published to public/.
     */
    public function hasPublishedAssets(): bool
    {
        return is_dir($this->publicPath());
    }

    /**
     * Publish assets from resources/dist/ to public/plugins/{id}/.
     * Returns true on success, false if dist/ doesn't exist.
     */
    public function publishAssets(): bool
    {
        $distPath = $this->basePath . '/resources/dist';

        if (! is_dir($distPath)) {
            return false;
        }

        $dest = $this->publicPath();

        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $this->copyDirectory($distPath, $dest);

        return true;
    }

    /**
     * Publish translations from resources/lang/ or lang/ to lang/vendor/{id}/.
     * Always overwrites existing files. Returns true if translations were published.
     */
    public function publishTranslations(): bool
    {
        $src = $this->translationsPath();

        if (! $src || ! is_dir($src)) {
            return false;
        }

        if (! function_exists('lang_path')) {
            return false;
        }

        $dest = lang_path('vendor/' . $this->id());

        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $this->copyDirectory($src, $dest);

        return true;
    }

    /**
     * Remove published assets from public/plugins/{id}/.
     */
    public function removePublishedAssets(): void
    {
        $dest = $this->publicPath();

        if (is_dir($dest)) {
            $this->removeDirectory($dest);
        }
    }

    /**
     * Recursively copy a directory.
     */
    private function copyDirectory(string $src, string $dst): void
    {
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $src . DIRECTORY_SEPARATOR . $item;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $item;

            if (is_dir($srcPath)) {
                if (! is_dir($dstPath)) {
                    mkdir($dstPath, 0755, true);
                }
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $path): void
    {
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($full)) {
                $this->removeDirectory($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }

    /**
     * Get source CSS path for the plugin (if exists).
     * Expected at: {plugin}/resources/css/app.css
     */
    public function sourceCssPath(): ?string
    {
        $path = $this->basePath . '/resources/css/app.css';

        return file_exists($path) ? $path : null;
    }

    /**
     * Get the full namespace for Filament resources.
     */
    public function filamentResourcesNamespace(): ?string
    {
        $namespace = $this->namespace();

        return $namespace ? $namespace . '\\Filament\\Resources' : null;
    }

    /**
     * Get the full namespace for Filament pages.
     */
    public function filamentPagesNamespace(): ?string
    {
        $namespace = $this->namespace();

        return $namespace ? $namespace . '\\Filament\\Pages' : null;
    }

    /**
     * Get the full namespace for Filament widgets.
     */
    public function filamentWidgetsNamespace(): ?string
    {
        $namespace = $this->namespace();

        return $namespace ? $namespace . '\\Filament\\Widgets' : null;
    }

    /**
     * Get all manifest data as array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'name' => $this->name(),
            'version' => $this->version(),
            'description' => $this->description(),
            'author' => $this->author(),
            'requires' => $this->requires(),
            'hooks' => [
                'public' => $this->publicHooks(),
                'private' => $this->privateHooks(),
            ],
            'plugin_class' => $this->pluginClass(),
            'namespace' => $this->namespace(),
            'autoload' => $this->autoload(),
            'base_path' => $this->basePath(),
        ];
    }
}
