<?php

namespace Miran\Mksine\Core\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Models\Theme;

class ThemeManager
{
    /**
     * Cache key for discovered themes (v2 stores plain arrays, not ThemeData objects).
     */
    protected const CACHE_KEY = 'mksine.themes.discovered.v2';

    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600;

    /**
     * Currently active theme data (cached in memory).
     */
    protected ?ThemeData $activeThemeData = null;

    /**
     * Get the package themes directory path.
     */
    public function getPackageThemesPath(): string
    {
        return dirname(__DIR__, 3).'/resources/views/themes';
    }

    /**
     * Get the project themes directory path.
     */
    public function getProjectThemesPath(): string
    {
        return resource_path('views/themes');
    }

    /**
     * Discover all available themes from both locations.
     *
     * @return Collection<string, ThemeData>
     */
    public function discover(bool $fresh = false): Collection
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $this->collectionFromCachedArrays($cached);
        }

        if ($cached !== null) {
            // Legacy or corrupted cache (e.g. serialized ThemeData after a package update).
            Cache::forget(self::CACHE_KEY);
        }

        $themes = $this->discoverThemes();

        Cache::put(self::CACHE_KEY, $this->collectionToCachedArrays($themes), self::CACHE_TTL);

        return $themes;
    }

    /**
     * Scan package and project theme directories.
     *
     * @return Collection<string, ThemeData>
     */
    protected function discoverThemes(): Collection
    {
        $themes = collect();

        $themes = $themes->merge($this->discoverFromPath(
            $this->getPackageThemesPath(),
            'package'
        ));

        return $themes->merge($this->discoverFromPath(
            $this->getProjectThemesPath(),
            'project'
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $cached
     * @return Collection<string, ThemeData>
     */
    protected function collectionFromCachedArrays(array $cached): Collection
    {
        $themes = collect();

        foreach ($cached as $identifier => $data) {
            if (! is_string($identifier) || ! is_array($data)) {
                continue;
            }

            try {
                $themes->put($identifier, ThemeData::fromArray($data));
            } catch (\Throwable $exception) {
                logger()->warning("Skipping invalid cached theme [{$identifier}]: {$exception->getMessage()}");
            }
        }

        return $themes;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function collectionToCachedArrays(Collection $themes): array
    {
        return $themes
            ->map(fn (ThemeData $theme): array => $theme->toArray())
            ->all();
    }

    /**
     * Discover themes from a specific path.
     *
     * @return Collection<string, ThemeData>
     */
    protected function discoverFromPath(string $basePath, string $source): Collection
    {
        $themes = collect();

        if (! File::isDirectory($basePath)) {
            return $themes;
        }

        $directories = File::directories($basePath);

        foreach ($directories as $directory) {
            $identifier = basename($directory);
            $themeJsonPath = $directory.'/theme.json';

            // Skip if no theme.json exists
            if (! File::exists($themeJsonPath)) {
                continue;
            }

            try {
                $json = json_decode(File::get($themeJsonPath), true, 512, JSON_THROW_ON_ERROR);
                $themeData = ThemeData::fromJson($json, $identifier, $directory, $source);
                $themes->put($identifier, $themeData);
            } catch (\JsonException $e) {
                // Log invalid theme.json and skip
                logger()->warning("Invalid theme.json in {$directory}: ".$e->getMessage());
            }
        }

        return $themes;
    }

    /**
     * Get a specific theme by identifier.
     */
    public function get(string $identifier): ?ThemeData
    {
        return $this->discover()->get($identifier);
    }

    /**
     * Get the currently active theme data.
     */
    public function getActive(): ?ThemeData
    {
        if ($this->activeThemeData !== null) {
            return $this->activeThemeData;
        }

        $activeTheme = Theme::active();

        if (! $activeTheme) {
            // Return default theme if no active theme is set
            return $this->getDefault();
        }

        $this->activeThemeData = $this->get($activeTheme->identifier);

        return $this->activeThemeData;
    }

    /**
     * Get the default theme (first package theme or first available).
     */
    public function getDefault(): ?ThemeData
    {
        $themes = $this->discover();

        // Prefer 'mksine' as default
        if ($themes->has('mksine')) {
            return $themes->get('mksine');
        }

        // Otherwise, return first package theme
        $packageTheme = $themes->first(fn (ThemeData $theme) => $theme->isPackageTheme());

        if ($packageTheme) {
            return $packageTheme;
        }

        // Otherwise, return first available theme
        return $themes->first();
    }

    /**
     * Activate a theme by identifier.
     */
    public function activate(string $identifier): bool
    {
        $themeData = $this->get($identifier);

        if (! $themeData) {
            return false;
        }

        // Get or create theme record
        $theme = Theme::firstOrCreate(
            ['identifier' => $identifier],
            ['is_active' => false]
        );

        // Clear cached active theme
        $this->activeThemeData = null;

        $activated = $theme->activate();

        if ($activated && function_exists('lang_path')) {
            $this->publishThemeTranslations($identifier);
        }

        return $activated;
    }

    /**
     * Get theme translations source path (lang/ or resources/lang/).
     */
    public function getThemeTranslationsPath(ThemeData $theme): ?string
    {
        $path = $theme->path.'/resources/lang';
        if (File::isDirectory($path)) {
            return $path;
        }
        $path = $theme->path.'/lang';

        return File::isDirectory($path) ? $path : null;
    }

    /**
     * Publish theme translations to lang/vendor/theme-{identifier}/.
     * Always overwrites. Returns true if translations were published.
     */
    public function publishThemeTranslations(string $identifier): bool
    {
        if (! function_exists('lang_path')) {
            return false;
        }

        $theme = $this->get($identifier);

        if (! $theme) {
            return false;
        }

        $src = $this->getThemeTranslationsPath($theme);

        if (! $src) {
            return false;
        }

        $dest = lang_path('vendor/theme-'.$identifier);

        if (! File::isDirectory($dest)) {
            File::ensureDirectoryExists($dest);
        }

        File::copyDirectory($src, $dest);

        return true;
    }

    /**
     * Get the view namespace for the active theme.
     */
    public function getViewNamespace(): string
    {
        $theme = $this->getActive();

        if (! $theme) {
            return 'mksine::themes.mksine';
        }

        if ($theme->isPackageTheme()) {
            return "mksine::themes.{$theme->identifier}";
        }

        // Project themes use a dynamic namespace
        return "theme::{$theme->identifier}";
    }

    /**
     * Get the full view name for a theme view.
     */
    public function view(string $view): string
    {
        return $this->getViewNamespace().'.'.$view;
    }

    /**
     * Get the layout view name.
     */
    public function layout(): string
    {
        return $this->view('layouts.index');
    }

    /**
     * Get the public URL for a theme asset.
     */
    public function asset(string $path): string
    {
        $theme = $this->getActive();

        if (! $theme) {
            return '';
        }

        // Project themes: assets are in public/themes/{identifier}/
        if ($theme->isProjectTheme()) {
            return asset("themes/{$theme->identifier}/{$path}");
        }

        // Package themes: assets are in public/vendor/mksine/themes/{identifier}/
        return asset("vendor/mksine/themes/{$theme->identifier}/{$path}");
    }

    /**
     * Publish theme assets to public directory.
     * Copies dist/ folder and other assets (images, screenshot) to public.
     */
    public function publishAssets(string $identifier): bool
    {
        $theme = $this->get($identifier);

        if (! $theme) {
            return false;
        }

        $destinationBase = $theme->isProjectTheme()
            ? public_path("themes/{$identifier}")
            : public_path("vendor/mksine/themes/{$identifier}");

        // Ensure destination directory exists
        File::ensureDirectoryExists($destinationBase);

        $published = false;

        // Copy dist/ folder
        $distPath = $theme->path.'/dist';
        if (File::isDirectory($distPath)) {
            $distDest = $destinationBase.'/dist';
            File::ensureDirectoryExists($distDest);
            File::copyDirectory($distPath, $distDest);
            $published = true;
        }

        // Copy images/ folder if exists
        $imagesPath = $theme->path.'/images';
        if (File::isDirectory($imagesPath)) {
            $imagesDest = $destinationBase.'/images';
            File::ensureDirectoryExists($imagesDest);
            File::copyDirectory($imagesPath, $imagesDest);
            $published = true;
        }

        // Copy screenshot
        if ($theme->screenshot) {
            $screenshotPath = $theme->path.'/'.$theme->screenshot;
            if (File::exists($screenshotPath) && is_file($screenshotPath)) {
                $screenshotDest = $destinationBase.'/'.$theme->screenshot;
                File::copy($screenshotPath, $screenshotDest);
                $published = true;
            }
        }

        return $published;
    }

    /**
     * Clear the theme cache.
     */
    public function clearCache(): void
    {
        Cache::forget('mksine.themes.discovered'); // legacy v1 key
        Cache::forget(self::CACHE_KEY);
        $this->activeThemeData = null;
    }

    /**
     * Get the URL for a theme's screenshot (served from theme path, no publish required).
     */
    public function getScreenshotUrl(ThemeData $theme): ?string
    {
        if (! $theme->screenshot) {
            return null;
        }

        $screenshotPath = $theme->path.'/'.$theme->screenshot;

        if (! File::exists($screenshotPath) || ! is_file($screenshotPath)) {
            return null;
        }

        return route('mksine.theme.screenshot', ['identifier' => $theme->identifier]);
    }

    /**
     * Storage directory for admin-edited custom CSS/JS (overrides theme dist/custom.*).
     */
    public function getCustomStorageDir(): string
    {
        return storage_path('app/theme-custom');
    }

    /**
     * Get the storage path for a theme's custom CSS or JS file.
     *
     * @param  'css'|'js'  $type
     */
    public function getCustomStoragePath(string $identifier, string $type): string
    {
        if (! in_array($type, ['css', 'js'], true)) {
            throw new \InvalidArgumentException('Type must be "css" or "js".');
        }

        return $this->getCustomStorageDir().'/'.$identifier.'.'.$type;
    }

    /**
     * Check if admin-edited custom asset exists for a theme.
     *
     * @param  'css'|'js'  $type
     */
    public function hasCustomAsset(string $identifier, string $type): bool
    {
        return File::isFile($this->getCustomStoragePath($identifier, $type));
    }

    /**
     * Get custom CSS or JS content. Prefers storage (admin-edited), then theme dist file.
     *
     * @param  'css'|'js'  $type
     */
    public function getCustomContent(string $identifier, string $type): string
    {
        $storagePath = $this->getCustomStoragePath($identifier, $type);
        if (File::isFile($storagePath)) {
            return File::get($storagePath);
        }

        $theme = $this->get($identifier);
        if (! $theme) {
            return '';
        }

        $distPath = $theme->path.'/dist/custom.'.$type;

        return File::isFile($distPath) ? File::get($distPath) : '';
    }

    /**
     * Save custom CSS or JS (always to storage; used by Theme Manager).
     *
     * @param  'css'|'js'  $type
     */
    public function putCustomContent(string $identifier, string $type, string $content): bool
    {
        $dir = $this->getCustomStorageDir();
        File::ensureDirectoryExists($dir);

        $path = $this->getCustomStoragePath($identifier, $type);

        return File::put($path, $content) !== false;
    }

    /**
     * Path to JSON file storing extra CSS/JS file list (paths or URLs) per theme.
     */
    public function getExtraAssetsStoragePath(string $identifier): string
    {
        return $this->getCustomStorageDir().'/'.$identifier.'-extra-assets.json';
    }

    /**
     * Get list of extra CSS and JS files (paths or full URLs) for a theme.
     * These are loaded after theme assets without editing Blade.
     *
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    public function getExtraAssets(string $identifier): array
    {
        $path = $this->getExtraAssetsStoragePath($identifier);
        if (! File::isFile($path)) {
            return ['css' => [], 'js' => []];
        }
        $data = json_decode(File::get($path), true);
        if (! is_array($data)) {
            return ['css' => [], 'js' => []];
        }

        return [
            'css' => array_values(array_filter(array_map('trim', $data['css'] ?? []))),
            'js' => array_values(array_filter(array_map('trim', $data['js'] ?? []))),
        ];
    }

    /**
     * Save list of extra CSS/JS files for a theme.
     *
     * @param  array<int, string>  $css  Paths or full URLs
     * @param  array<int, string>  $js  Paths or full URLs
     */
    public function setExtraAssets(string $identifier, array $css, array $js): bool
    {
        $dir = $this->getCustomStorageDir();
        File::ensureDirectoryExists($dir);
        $path = $this->getExtraAssetsStoragePath($identifier);
        $data = [
            'css' => array_values(array_filter(array_map('trim', $css))),
            'js' => array_values(array_filter(array_map('trim', $js))),
        ];

        return File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    /**
     * Register project theme views with Laravel's view system.
     * Registers the "theme" hint so that view names like "theme::identifier.layouts.index"
     * resolve (Laravel uses the part before the first :: as the hint).
     */
    public function registerProjectThemeViews(): void
    {
        $projectThemesPath = $this->getProjectThemesPath();

        if (! File::isDirectory($projectThemesPath)) {
            return;
        }

        view()->addNamespace('theme', $projectThemesPath);

        $directories = File::directories($projectThemesPath);

        foreach ($directories as $directory) {
            $identifier = basename($directory);
            view()->addNamespace("theme::{$identifier}", $directory);
        }
    }
}
