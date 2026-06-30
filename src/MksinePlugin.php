<?php

namespace Miran\Mksine;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Plugins\Contracts\RegistersFilamentPlugins;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Support\Logging\MksineLog;

class MksinePlugin implements Plugin
{
    public function getId(): string
    {
        return 'mksine';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->colors([
                'primary' => Color::Blue
            ])
            ->spa()
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationSort(30)
                    ->navigationGroup(fn(): string => AdminSidebarNavigation::usesShopSidebar()
                        ? AdminSidebarNavigation::group(AdminSidebarNavigation::GROUP_USERS)
                        : AdminSidebarNavigation::accessControlGroup())
                    ->navigationLabel(fn(): string => AdminSidebarNavigation::usesShopSidebar()
                        ? __('mksine::common.access_rights')
                        : __('filament-shield::filament-shield.nav.role.label')),
            ]);

        // Register core MKS CMS resources and pages
        $panel
            ->discoverResources(__DIR__ . '/Filament/Resources', 'Miran\\Mksine\\Filament\\Resources')
            ->discoverClusters(__DIR__ . '/Filament/Clusters', 'Miran\\Mksine\\Filament\\Clusters')
            ->discoverPages(__DIR__ . '/Filament/Pages', 'Miran\\Mksine\\Filament\\Pages');

        // Discover and register active plugin Filament components
        $this->discoverPluginFilamentComponents($panel);

        $this->discoverActiveThemeFilamentComponents($panel);

        // Optional: Filament panel plugins from active CMS plugins (e.g. Activitylog) — not app panel code.
        $this->registerPluginFilamentPlugins($panel);

        $panel
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4.75rem');
    }

    public function boot(Panel $panel): void
    {
        $panel->navigationGroups(AdminSidebarNavigation::panelGroups());

        // Register MediaPickerModal component to be rendered on every page
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render('@livewire(\'mksine::media-picker-modal\')')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_NAV_START,
            fn(): string => view('mksine::filament.partials.sidebar-nav-search')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn(): string => view('mksine::filament.partials.sidebar-collapse-toggle')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn(): string => view('mksine::filament.partials.sidebar-store-fix')->render()
                . view('mksine::filament.partials.sidebar-nav-search-script')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn(): string => view('mksine::filament.partials.panel-styles-after')->render(),
        );
    }

    /**
     * Iterate over active plugins and register their compiled assets with Filament.
     * Each plugin is responsible for its own CSS/JS build (self-contained).
     * Called from MksineServiceProvider::packageBooted() so assets are available for filament:assets.
     *
     * **Global CSS hazard:** these stylesheets load on every panel page. A full Tailwind bundle
     * that includes Preflight (`@import "tailwindcss"`) will reset/base-overrides and break
     * Filament layouts (grids, cards, plugin/theme pages). Plugins should ship theme+utilities
     * only, or scope their CSS — see mks-booking `resources/css/app.css`.
     */
    public static function registerPluginAssets(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mks_plugins')) {
                return;
            }

            $pluginManager = app(PluginManager::class);

            if (! $pluginManager->isInitialized()) {
                $pluginManager->initialize();
            }

            $registry = $pluginManager->getRegistry();

            foreach ($registry->getManifests() as $pluginId => $manifest) {
                if (! $registry->isActive($pluginId)) {
                    continue;
                }

                $assets = [];

                // Prefer published URL (public/plugins/{id}/) over raw dist/ path.
                // This mirrors the theme system and works correctly after deployment.
                if ($cssUrl = $manifest->publishedCssUrl()) {
                    $assets[] = Css::make("{$pluginId}-styles", $cssUrl);
                } elseif ($cssPath = $manifest->distCssPath()) {
                    // Fallback: serve directly from plugin folder (dev only).
                    $assets[] = Css::make("{$pluginId}-styles", $cssPath);
                }

                if ($jsUrl = $manifest->publishedJsUrl()) {
                    $assets[] = Js::make("{$pluginId}-scripts", $jsUrl);
                } elseif ($jsPath = $manifest->distJsPath()) {
                    $assets[] = Js::make("{$pluginId}-scripts", $jsPath);
                }

                if (! empty($assets)) {
                    FilamentAsset::register($assets);
                    MksineLog::debug("Registered assets for plugin: {$pluginId}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to register plugin assets: ' . $e->getMessage());
        }
    }

    /**
     * Discover and register Filament components from active plugins.
     */
    protected function discoverPluginFilamentComponents(Panel $panel): void
    {
        try {
            // Check if database is ready and table exists
            if (! $this->isDatabaseReady()) {
                return;
            }

            $pluginManager = app(PluginManager::class);

            // Ensure plugin system is initialized
            if (! $pluginManager->isInitialized()) {
                $pluginManager->initialize();
            }

            $registry = $pluginManager->getRegistry();
            $manifests = $registry->getManifests();

            foreach ($manifests as $pluginId => $manifest) {
                // Only register components for active plugins
                if (! $registry->isActive($pluginId)) {
                    continue;
                }

                // Discover Resources
                $resourcesPath = $manifest->filamentResourcesPath();
                $resourcesNamespace = $manifest->filamentResourcesNamespace();

                if ($resourcesPath && $resourcesNamespace) {
                    $panel->discoverResources($resourcesPath, $resourcesNamespace);
                    MksineLog::debug("Discovered Filament resources for plugin: {$pluginId}", [
                        'path' => $resourcesPath,
                        'namespace' => $resourcesNamespace,
                    ]);
                }

                // Discover Pages
                $pagesPath = $manifest->filamentPagesPath();
                $pagesNamespace = $manifest->filamentPagesNamespace();

                if ($pagesPath && $pagesNamespace) {
                    $panel->discoverPages($pagesPath, $pagesNamespace);
                    MksineLog::debug("Discovered Filament pages for plugin: {$pluginId}", [
                        'path' => $pagesPath,
                        'namespace' => $pagesNamespace,
                    ]);
                }

                // Discover Widgets
                $widgetsPath = $manifest->filamentWidgetsPath();
                $widgetsNamespace = $manifest->filamentWidgetsNamespace();

                if ($widgetsPath && $widgetsNamespace) {
                    $panel->discoverWidgets($widgetsPath, $widgetsNamespace);
                    MksineLog::debug("Discovered Filament widgets for plugin: {$pluginId}", [
                        'path' => $widgetsPath,
                        'namespace' => $widgetsNamespace,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Log error but don't crash - plugin discovery is not critical for core functionality
            Log::warning('Failed to discover plugin Filament components: ' . $e->getMessage());
        }
    }

    /**
     * Discover Filament pages (and future resources/widgets) from the active theme's php/ tree.
     */
    protected function discoverActiveThemeFilamentComponents(Panel $panel): void
    {
        try {
            if (! $this->isDatabaseReady()) {
                return;
            }

            $theme = theme_manager()->getActive();
            if ($theme === null) {
                return;
            }

            $pagesPath = $theme->path . '/php/Filament/Pages';
            $pagesNamespace = 'Themes\\' . str_replace(['-', ' '], '', ucwords(str_replace('-', ' ', $theme->identifier))) . '\\Filament\\Pages';

            if (is_dir($pagesPath)) {
                $panel->discoverPages($pagesPath, $pagesNamespace);
                MksineLog::debug('Discovered Filament pages for active theme', [
                    'theme' => $theme->identifier,
                    'path' => $pagesPath,
                    'namespace' => $pagesNamespace,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to discover theme Filament components: ' . $e->getMessage());
        }
    }

    /**
     * Check if database is ready for plugin queries.
     */
    protected function isDatabaseReady(): bool
    {
        try {
            // Check if the mks_plugins table exists
            return \Illuminate\Support\Facades\Schema::hasTable('mks_plugins');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Let active plugins register additional Filament {@see Plugin} instances (rmsramos/activitylog, etc.).
     */
    protected function registerPluginFilamentPlugins(Panel $panel): void
    {
        try {
            if (! $this->isDatabaseReady()) {
                return;
            }

            $pluginManager = app(PluginManager::class);

            if (! $pluginManager->isInitialized()) {
                $pluginManager->initialize();
            }

            $registry = $pluginManager->getRegistry();

            foreach ($registry->getManifests() as $pluginId => $manifest) {
                if (! $registry->isActive($pluginId)) {
                    continue;
                }

                $instance = $pluginManager->instantiatePlugin($manifest);

                if (! $instance instanceof RegistersFilamentPlugins) {
                    continue;
                }

                foreach ($instance->filamentPlugins($panel) as $filamentPlugin) {
                    $panel->plugin($filamentPlugin);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to register plugin Filament packages: ' . $e->getMessage());
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
