<?php

use Miran\Mksine\Core\Theme\ThemeManager;

if (! function_exists('mks_setting')) {
    /**
     * Get a MKSine setting value by key.
     */
    function mks_setting(string $key): mixed
    {
        return \Miran\Mksine\Models\Setting::where('key', $key)->first()?->value;
    }
}

if (! function_exists('theme_manager')) {
    /**
     * Get the ThemeManager instance.
     */
    function theme_manager(): ThemeManager
    {
        return app(ThemeManager::class);
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Get the URL for a theme asset.
     */
    function theme_asset(string $path): string
    {
        return theme_manager()->asset($path);
    }
}

if (! function_exists('theme_view')) {
    /**
     * Get the full view name for a theme view.
     */
    function theme_view(string $view): string
    {
        return theme_manager()->view($view);
    }
}

if (! function_exists('theme_layout')) {
    /**
     * Get the theme layout view name.
     */
    function theme_layout(): string
    {
        return theme_manager()->layout();
    }
}

if (! function_exists('active_theme')) {
    /**
     * Get the active theme data.
     */
    function active_theme(): ?\Miran\Mksine\Core\Theme\ThemeData
    {
        return theme_manager()->getActive();
    }
}