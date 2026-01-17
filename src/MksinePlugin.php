<?php

namespace Miran\Mksine;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Plugins\PluginManager;

class MksinePlugin implements Plugin
{
    public function getId(): string
    {
        return 'mksine';
    }

    public function register(Panel $panel): void
    {
        // Register core MKS CMS resources and pages
        $panel
            ->discoverResources(__DIR__ . '/Filament/Resources', 'Miran\\Mksine\\Filament\\Resources')
            ->discoverPages(__DIR__ . '/Filament/Pages', 'Miran\\Mksine\\Filament\\Pages');

        // Discover and register active plugin Filament components
        $this->discoverPluginFilamentComponents($panel);
    }

    public function boot(Panel $panel): void
    {
        // Register MediaPickerModal component to be rendered on every page
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => Blade::render('@livewire(\'mksine::media-picker-modal\')')
        );
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
                    Log::debug("Discovered Filament resources for plugin: {$pluginId}", [
                        'path' => $resourcesPath,
                        'namespace' => $resourcesNamespace,
                    ]);
                }

                // Discover Pages
                $pagesPath = $manifest->filamentPagesPath();
                $pagesNamespace = $manifest->filamentPagesNamespace();

                if ($pagesPath && $pagesNamespace) {
                    $panel->discoverPages($pagesPath, $pagesNamespace);
                    Log::debug("Discovered Filament pages for plugin: {$pluginId}", [
                        'path' => $pagesPath,
                        'namespace' => $pagesNamespace,
                    ]);
                }

                // Discover Widgets
                $widgetsPath = $manifest->filamentWidgetsPath();
                $widgetsNamespace = $manifest->filamentWidgetsNamespace();

                if ($widgetsPath && $widgetsNamespace) {
                    $panel->discoverWidgets($widgetsPath, $widgetsNamespace);
                    Log::debug("Discovered Filament widgets for plugin: {$pluginId}", [
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
