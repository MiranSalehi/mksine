<?php

namespace Miran\Mksine\Core\Theme;

use Illuminate\Support\Facades\Blade;

class ThemeBladeDirectives
{
    /**
     * Register all theme-related blade directives.
     */
    public static function register(): void
    {
        static::registerThemeAssets();
        static::registerThemeView();
        static::registerThemeDoAction();
    }

    /**
     * Register @themeDoAction directive.
     * Usage: @themeDoAction('home.before_hero') — runs all callbacks registered with theme_add_action().
     */
    protected static function registerThemeDoAction(): void
    {
        Blade::directive('themeDoAction', function ($expression) {
            return "<?php echo theme_do_action({$expression}); ?>";
        });
    }

    /**
     * Register @themeAssets directive.
     *
     * Renders CSS and JS tags for the active theme's compiled assets.
     * Assets are loaded from public/themes/{identifier}/ (project themes)
     * or public/vendor/mksine/themes/{identifier}/ (package themes).
     */
    protected static function registerThemeAssets(): void
    {
        Blade::directive('themeAssets', function () {
            return '<?php echo \Miran\Mksine\Core\Theme\ThemeBladeDirectives::renderThemeAssets(); ?>';
        });
    }

    /**
     * Register @themeView directive.
     *
     * Usage: @themeView('home') -> renders the home view from active theme
     */
    protected static function registerThemeView(): void
    {
        Blade::directive('themeView', function ($expression) {
            return "<?php echo view(theme_view({$expression}))->render(); ?>";
        });
    }

    /**
     * Render theme assets (CSS and JS tags) for the active theme.
     * Custom CSS/JS (dist/custom.css, dist/custom.js) are served from storage when edited in Theme Manager.
     */
    public static function renderThemeAssets(): string
    {
        $theme = theme_manager()->getActive();

        if (! $theme) {
            return '';
        }

        $manager = theme_manager();
        $html = '';

        // Render CSS tags
        foreach ($theme->getCssAssets() as $css) {
            $url = static::resolveAssetUrl($theme, $css, $manager);
            $html .= '<link rel="stylesheet" href="' . e($url) . '">' . "\n";
        }

        // Render JS tags
        foreach ($theme->getJsAssets() as $js) {
            $url = static::resolveAssetUrl($theme, $js, $manager);
            $html .= '<script src="' . e($url) . '" defer></script>' . "\n";
        }

        // Extra CSS/JS files (added from Theme Manager, no Blade edit)
        $extra = $manager->getExtraAssets($theme->identifier);
        foreach ($extra['css'] as $pathOrUrl) {
            $url = static::resolveExtraAssetUrl($theme, $pathOrUrl);
            $html .= '<link rel="stylesheet" href="' . e($url) . '">' . "\n";
        }
        foreach ($extra['js'] as $pathOrUrl) {
            $url = static::resolveExtraAssetUrl($theme, $pathOrUrl);
            $html .= '<script src="' . e($url) . '" defer></script>' . "\n";
        }

        // Enqueued assets (theme_enqueue_style / theme_enqueue_script – no Blade edit)
        $enqueue = theme_enqueue();
        foreach ($enqueue->getStyles() as $item) {
            $url = static::resolveExtraAssetUrl($theme, $item['url']);
            $attrs = array_merge(['rel' => 'stylesheet', 'href' => $url], $item['attributes']);
            $html .= '<link ' . static::attributesToString($attrs) . '>' . "\n";
        }
        foreach ($enqueue->getScripts() as $item) {
            $url = static::resolveExtraAssetUrl($theme, $item['url']);
            $attrs = array_merge(['src' => $url], $item['attributes']);
            if (! isset($attrs['defer']) && ! isset($attrs['async'])) {
                $attrs['defer'] = 'defer';
            }
            $html .= '<script ' . static::attributesToString($attrs) . '></script>' . "\n";
        }

        return $html;
    }

    protected static function attributesToString(array $attributes): string
    {
        $pairs = [];
        foreach ($attributes as $key => $value) {
            if ($value === true || $value === '') {
                $pairs[] = e($key);
            } else {
                $pairs[] = e($key) . '="' . e($value) . '"';
            }
        }

        return implode(' ', $pairs);
    }

    /**
     * Resolve URL for an extra asset (path relative to theme or full URL).
     */
    protected static function resolveExtraAssetUrl(ThemeData $theme, string $pathOrUrl): string
    {
        $pathOrUrl = trim($pathOrUrl);
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://') || str_starts_with($pathOrUrl, '//')) {
            return $pathOrUrl;
        }
        $manager = theme_manager();
        return static::resolveAssetUrl($theme, $pathOrUrl, $manager);
    }

    /**
     * Resolve the full URL for an asset path.
     * Custom assets (dist/custom.css, dist/custom.js) use storage route when present.
     */
    protected static function resolveAssetUrl(ThemeData $theme, string $path, ?ThemeManager $manager = null): string
    {
        $manager = $manager ?? theme_manager();

        // If already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        // Admin-edited custom CSS/JS: serve from storage when present
        if ($path === 'dist/custom.css' && $manager->hasCustomAsset($theme->identifier, 'css')) {
            return route('mksine.theme.custom.asset', ['identifier' => $theme->identifier, 'type' => 'css']);
        }
        if ($path === 'dist/custom.js' && $manager->hasCustomAsset($theme->identifier, 'js')) {
            return route('mksine.theme.custom.asset', ['identifier' => $theme->identifier, 'type' => 'js']);
        }

        // Project themes: assets are in public/themes/{identifier}/
        if ($theme->isProjectTheme()) {
            return static::versionedAssetUrl("themes/{$theme->identifier}/{$path}");
        }

        // Package themes: assets are in public/vendor/mksine/themes/{identifier}/
        return static::versionedAssetUrl("vendor/mksine/themes/{$theme->identifier}/{$path}");
    }

    /**
     * Append ?v=<filemtime> so deploys bust CDN/browser caches for static theme
     * assets. Without it, long-lived edge caches keep serving pre-deploy builds.
     */
    protected static function versionedAssetUrl(string $publicPath): string
    {
        $url = asset($publicPath);

        $file = public_path($publicPath);
        if (is_file($file)) {
            $url .= '?v='.filemtime($file);
        }

        return $url;
    }
}
