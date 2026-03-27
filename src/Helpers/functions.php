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

if (! function_exists('theme_enqueue')) {
    /**
     * Get the ThemeEnqueue instance (WordPress-style enqueue API).
     */
    function theme_enqueue(): \Miran\Mksine\Core\Theme\ThemeEnqueue
    {
        return app(\Miran\Mksine\Core\Theme\ThemeEnqueue::class);
    }
}

if (! function_exists('theme_enqueue_style')) {
    /**
     * Enqueue a CSS file. Output when @themeAssets is rendered. No Blade edit needed.
     *
     * @param  array<string, string>  $attributes  e.g. ['media' => 'print']
     */
    function theme_enqueue_style(string $url, array $attributes = []): void
    {
        theme_enqueue()->enqueueStyle($url, $attributes);
    }
}

if (! function_exists('theme_enqueue_script')) {
    /**
     * Enqueue a JS file. Output when @themeAssets is rendered. No Blade edit needed.
     *
     * @param  array<string, string>  $attributes  e.g. ['defer' => 'defer']
     */
    function theme_enqueue_script(string $url, array $attributes = []): void
    {
        theme_enqueue()->enqueueScript($url, $attributes);
    }
}

if (! function_exists('theme_register_override')) {
    /**
     * Register a theme override for a frontend page (call from theme.php or theme code).
     * Page keys: home, category-list, category-show, post-list, post-show, page-show, author-show.
     */
    function theme_register_override(string $page, string $componentClass): void
    {
        app(\Miran\Mksine\Core\Theme\ThemeRegistry::class)->registerOverride($page, $componentClass);
    }
}

if (! function_exists('theme_register_routes')) {
    /**
     * Register extra routes for the active theme (call from theme.php).
     * Callback receives nothing; use Route:: facade inside it.
     *
     * @param  callable(): void  $callback
     */
    function theme_register_routes(callable $callback): void
    {
        app(\Miran\Mksine\Core\Theme\ThemeRegistry::class)->registerRoutes($callback);
    }
}

if (! function_exists('theme_bootstrap')) {
    /**
     * Load active theme's theme.php (run once at start of web routes).
     */
    function theme_bootstrap(): void
    {
        app(\Miran\Mksine\Core\Theme\ThemeBootstrap::class)->boot();
    }
}

if (! function_exists('theme_add_action')) {
    /**
     * Add a callback to a theme template hook (WordPress-style). Fired when @themeDoAction($hook) runs.
     *
     * @param  string  $hook  e.g. 'home.before_hero', 'home.after_section_latest'
     * @param  callable  $callback  Function that may return HTML string or echo
     * @param  int  $priority  Lower runs first (default 10)
     */
    function theme_add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(\Miran\Mksine\Core\Theme\ThemeActionManager::class)->addAction($hook, $callback, $priority);
    }
}

if (! function_exists('theme_do_action')) {
    /**
     * Fire a theme template hook: run all callbacks registered with theme_add_action($hook).
     * Returns concatenated output; use in Blade via @themeDoAction('hook_name').
     *
     * @param  string  $hook  Hook name
     * @param  array<string, mixed>  $args  Optional arguments passed to each callback
     * @return string Combined output from all callbacks
     */
    function theme_do_action(string $hook, array $args = []): string
    {
        return app(\Miran\Mksine\Core\Theme\ThemeActionManager::class)->doAction($hook, $args);
    }
}