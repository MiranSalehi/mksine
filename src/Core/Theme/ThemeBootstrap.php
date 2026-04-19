<?php

namespace Miran\Mksine\Core\Theme;

use Illuminate\Support\Facades\File;

/**
 * Loads active theme's theme.php and registers its php/ for autoload.
 */
class ThemeBootstrap
{
    public function boot(): void
    {
        try {
            $theme = theme_manager()->getActive();
        } catch (\Throwable) {
            return;
        }
        if (! $theme) {
            return;
        }

        $themePath = $theme->path;
        $themeFile = $themePath . '/theme.php';

        if (! File::isFile($themeFile)) {
            return;
        }

        self::registerAutoloadForTheme($theme->identifier, $themePath);

        $registry = app(ThemeRegistry::class);
        $registrar = new ThemeRegistrar($registry);

        (function () use ($themeFile, $registrar) {
            $register_override = function (string $page, string $componentClass) use ($registrar) {
                $registrar->registerOverride($page, $componentClass);
            };
            $register_routes = function (callable $callback) use ($registrar) {
                $registrar->registerRoutes($callback);
            };
            require $themeFile;
        })();
    }

    /**
     * Register PSR-4 for the active theme's php/ directory. Idempotent; safe to call multiple times.
     *
     * Livewire sub-requests (e.g. POST /livewire/update) do not load mksine routes/web.php, so
     * {@see boot()} never runs and theme classes would not autoload without this.
     */
    public static function ensureActiveThemeAutoloadRegistered(): void
    {
        try {
            $theme = theme_manager()->getActive();
        } catch (\Throwable) {
            return;
        }

        if ($theme === null) {
            return;
        }

        self::registerAutoloadForTheme($theme->identifier, $theme->path);
    }

    public static function registerAutoloadForTheme(string $identifier, string $themePath): void
    {
        $phpPath = $themePath . '/php';
        if (! File::isDirectory($phpPath)) {
            return;
        }

        $namespace = 'Themes\\' . str_replace(['-', ' '], '', ucwords(str_replace('-', ' ', $identifier))) . '\\';
        $loader = require base_path('vendor/autoload.php');
        if (method_exists($loader, 'addPsr4')) {
            $loader->addPsr4($namespace, $phpPath);
        }
    }
}
