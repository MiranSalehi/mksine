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
     */
    public static function renderThemeAssets(): string
    {
        $theme = theme_manager()->getActive();

        if (! $theme) {
            return '';
        }

        $html = '';

        // Render CSS tags
        foreach ($theme->getCssAssets() as $css) {
            $url = static::resolveAssetUrl($theme, $css);
            $html .= '<link rel="stylesheet" href="' . e($url) . '">' . "\n";
        }

        // Render JS tags
        foreach ($theme->getJsAssets() as $js) {
            $url = static::resolveAssetUrl($theme, $js);
            $html .= '<script src="' . e($url) . '" defer></script>' . "\n";
        }

        return $html;
    }

    /**
     * Resolve the full URL for an asset path.
     */
    protected static function resolveAssetUrl(ThemeData $theme, string $path): string
    {
        // If already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        // Project themes: assets are in public/themes/{identifier}/
        if ($theme->isProjectTheme()) {
            return asset("themes/{$theme->identifier}/{$path}");
        }

        // Package themes: assets are in public/vendor/mksine/themes/{identifier}/
        return asset("vendor/mksine/themes/{$theme->identifier}/{$path}");
    }
}
