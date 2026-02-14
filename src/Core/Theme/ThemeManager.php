<?php

namespace Miran\Mksine\Core\Theme;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Models\Theme;

class ThemeManager
{
    /**
     * Cache key for discovered themes.
     */
    protected const CACHE_KEY = 'mksine.themes.discovered';

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
        return dirname(__DIR__, 3) . '/resources/views/themes';
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

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $themes = collect();

            // Discover package themes
            $packageThemes = $this->discoverFromPath(
                $this->getPackageThemesPath(),
                'package'
            );
            $themes = $themes->merge($packageThemes);

            // Discover project themes
            $projectThemes = $this->discoverFromPath(
                $this->getProjectThemesPath(),
                'project'
            );
            $themes = $themes->merge($projectThemes);

            return $themes;
        });
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
            $themeJsonPath = $directory . '/theme.json';

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
                logger()->warning("Invalid theme.json in {$directory}: " . $e->getMessage());
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

        return $theme->activate();
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
        return $this->getViewNamespace() . '.' . $view;
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
        $distPath = $theme->path . '/dist';
        if (File::isDirectory($distPath)) {
            $distDest = $destinationBase . '/dist';
            File::ensureDirectoryExists($distDest);
            File::copyDirectory($distPath, $distDest);
            $published = true;
        }

        // Copy images/ folder if exists
        $imagesPath = $theme->path . '/images';
        if (File::isDirectory($imagesPath)) {
            $imagesDest = $destinationBase . '/images';
            File::ensureDirectoryExists($imagesDest);
            File::copyDirectory($imagesPath, $imagesDest);
            $published = true;
        }

        // Copy screenshot
        if ($theme->screenshot) {
            $screenshotPath = $theme->path . '/' . $theme->screenshot;
            if (File::exists($screenshotPath) && is_file($screenshotPath)) {
                $screenshotDest = $destinationBase . '/' . $theme->screenshot;
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

        $screenshotPath = $theme->path . '/' . $theme->screenshot;

        if (! File::exists($screenshotPath) || ! is_file($screenshotPath)) {
            return null;
        }

        return route('mksine.theme.screenshot', ['identifier' => $theme->identifier]);
    }

    /**
     * Register project theme views with Laravel's view system.
     */
    public function registerProjectThemeViews(): void
    {
        $projectThemesPath = $this->getProjectThemesPath();

        if (! File::isDirectory($projectThemesPath)) {
            return;
        }

        $directories = File::directories($projectThemesPath);

        foreach ($directories as $directory) {
            $identifier = basename($directory);
            view()->addNamespace("theme::{$identifier}", $directory);
        }
    }
}
