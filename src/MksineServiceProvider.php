<?php

namespace Miran\Mksine;

use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\GeoCityPolicy;
use App\Policies\GeoCountryPolicy;
use App\Policies\GeoStatePolicy;
use App\Policies\MediaPolicy;
use App\Policies\MenuLocationPolicy;
use App\Policies\MenuPolicy;
use App\Policies\PagePolicy;
use App\Policies\PostPolicy;
use App\Policies\RolePolicy;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\PanelRegistry;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Miran\Mksine\Commands\ArtisanCommand;
use Miran\Mksine\Commands\MksineCommand;
use Miran\Mksine\Commands\MksineInstallCommand;
use Miran\Mksine\Console\Commands\ConsoleRunDetachedCommand;
use Miran\Mksine\Console\Commands\CreateSuperAdminCommand;
use Miran\Mksine\Console\Commands\DiscoverHooksCommand;
use Miran\Mksine\Console\Commands\FreshSuperAdminCommand;
use Miran\Mksine\Console\Commands\GeoImportCommand;
use Miran\Mksine\Console\Commands\GeoMigrateLegacyIranCommand;
use Miran\Mksine\Console\Commands\GeoSyncCityNativeNamesCommand;
use Miran\Mksine\Console\Commands\MigrateSmartCommand;
use Miran\Mksine\Console\Commands\PluginActivateCommand;
use Miran\Mksine\Console\Commands\PluginDeactivateCommand;
use Miran\Mksine\Console\Commands\PluginDiscoverCommand;
use Miran\Mksine\Console\Commands\PluginInstallCommand;
use Miran\Mksine\Console\Commands\PluginListCommand;
use Miran\Mksine\Console\Commands\PluginMakeCommand;
use Miran\Mksine\Console\Commands\PluginMakeModelCommand;
use Miran\Mksine\Console\Commands\PluginMakePageCommand;
use Miran\Mksine\Console\Commands\PluginMakeResourceCommand;
use Miran\Mksine\Console\Commands\PluginMakeWidgetCommand;
use Miran\Mksine\Console\Commands\PluginMigrateCommand;
use Miran\Mksine\Console\Commands\PluginPublishCommand;
use Miran\Mksine\Console\Commands\PluginPublishLangCommand;
use Miran\Mksine\Console\Commands\PluginUninstallCommand;
use Miran\Mksine\Console\Commands\ReleaseArchiveCommand;
use Miran\Mksine\Console\Commands\RollbackCoreCommand;
use Miran\Mksine\Console\Commands\RollbackPluginCommand;
use Miran\Mksine\Console\Commands\RollbackThemeCommand;
use Miran\Mksine\Console\Commands\ThemeMakeCommand;
use Miran\Mksine\Console\Commands\ThemePublishCommand;
use Miran\Mksine\Console\Commands\ThemePublishLangCommand;
use Miran\Mksine\Console\Commands\UpdateCoreCommand;
use Miran\Mksine\Console\Commands\UpdatePluginCommand;
use Miran\Mksine\Console\Commands\UpdateThemeCommand;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Core\Hooks\HookAsyncDispatcherInterface;
use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\LaravelHookAsyncDispatcher;
use Miran\Mksine\Core\Hooks\MenuItemSourceManager;
use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Core\Hooks\SettingsTabManager;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Core\MenuItemSources\CategoryMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\CustomLinkMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\PageMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\PostMenuItemSource;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\AccordionComponent;
use Miran\Mksine\Core\PageBuilder\Components\ButtonComponent;
use Miran\Mksine\Core\PageBuilder\Components\CallToActionComponent;
use Miran\Mksine\Core\PageBuilder\Components\ColumnsComponent;
use Miran\Mksine\Core\PageBuilder\Components\ContainerInsetComponent;
use Miran\Mksine\Core\PageBuilder\Components\DividerComponent;
use Miran\Mksine\Core\PageBuilder\Components\FeatureListComponent;
use Miran\Mksine\Core\PageBuilder\Components\GridLayoutComponent;
use Miran\Mksine\Core\PageBuilder\Components\HeadingComponent;
use Miran\Mksine\Core\PageBuilder\Components\HeroComponent;
use Miran\Mksine\Core\PageBuilder\Components\ImageComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineClinicFeaturesGridComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFeaturedDomainsComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineFinanceShowcaseComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineHeroDomainComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksinePostCommentsFeedComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineServicesTrioComponent;
use Miran\Mksine\Core\PageBuilder\Components\MksineTestimonialsGridComponent;
use Miran\Mksine\Core\PageBuilder\Components\SliderComponent;
use Miran\Mksine\Core\PageBuilder\Components\SpacerComponent;
use Miran\Mksine\Core\PageBuilder\Components\TabsComponent;
use Miran\Mksine\Core\PageBuilder\Components\TextComponent;
use Miran\Mksine\Core\PageBuilder\Livewire\ComponentEditor;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;
use Miran\Mksine\Core\PageBuilder\Templates\AboutPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\BlankTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\ContactPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\LandingPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\MksineDefaultHomeTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\ServicesPageTemplate;
use Miran\Mksine\Core\Plugins\PluginLogger;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Plugins\PluginManifestTranslator;
use Miran\Mksine\Core\Theme\ThemeActionManager;
use Miran\Mksine\Core\Theme\ThemeBladeDirectives;
use Miran\Mksine\Core\Theme\ThemeBootstrap;
use Miran\Mksine\Core\Theme\ThemeEnqueue;
use Miran\Mksine\Core\Theme\ThemeLivewireMissingComponentResolver;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Core\Theme\ThemeRegistry;
use Miran\Mksine\Core\Translation\AdminTranslationManager;
use Miran\Mksine\Core\Translation\MksineFileLoader;
use Miran\Mksine\Core\Translation\TranslationFileManager;
use Miran\Mksine\Filament\Support\FilamentPanelDashboard;
use Miran\Mksine\Filament\Support\MksFilamentDateMacros;
use Miran\Mksine\Livewire\Frontend\CategoryList;
use Miran\Mksine\Livewire\Frontend\CategoryShow;
use Miran\Mksine\Livewire\Frontend\FrontendResolver;
use Miran\Mksine\Livewire\Frontend\Home;
use Miran\Mksine\Livewire\Frontend\PageShow;
use Miran\Mksine\Livewire\Frontend\PostComments;
use Miran\Mksine\Livewire\Frontend\PostList;
use Miran\Mksine\Livewire\Frontend\PostShow;
use Miran\Mksine\Livewire\MediaPickerModal;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Services\Geo\GeoResolver;
use Miran\Mksine\Services\Geo\StoreGeoSettings;
use Miran\Mksine\Services\MenuService;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Miran\Mksine\Support\FilesystemPath;
use Miran\Mksine\Support\LivewireUploadConfiguration;
use Miran\Mksine\Testing\TestsMksine;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MksineServiceProvider extends PackageServiceProvider
{
    public static string $name = 'mksine';

    public static string $viewNamespace = 'mksine';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasRoutes('web');

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        // Migrations are published directly in packageBooted() method
        // to support timestamped migration files instead of .stub files

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AdminConsoleProcessManager::class, function (): AdminConsoleProcessManager {
            return new AdminConsoleProcessManager(base_path());
        });

        require_once __DIR__.'/Helpers/functions.php';

        $this->app->extend('translation.loader', function (FileLoader $loader, $app) {
            $merged = new MksineFileLoader($app['files'], $loader->paths());
            foreach ($loader->namespaces() as $namespace => $hint) {
                $merged->addNamespace($namespace, $hint);
            }
            foreach ($loader->jsonPaths() as $jsonPath) {
                $merged->addJsonPath($jsonPath);
            }

            // Register mksine on the loader immediately. Translator::fireResolvingCallbacks runs
            // global "resolving" listeners before "afterResolving" (where loadTranslationsFrom adds
            // namespaces). If those listeners call __() for mksine::*, hints were empty and
            // MksineFileLoader cached an empty group forever.
            $mksinePackageLang = realpath(__DIR__.'/../resources/lang');
            if ($mksinePackageLang !== false) {
                $merged->addNamespace('mksine', $mksinePackageLang);
            }

            return $merged;
        });

        $this->app->singleton(Mksine::class, function () {
            return new Mksine;
        });

        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            foreach ($registry->all() as $panel) {
                FilamentPanelDashboard::replaceHostDefaultDashboard($panel);
            }
        });

        $this->app->singleton(HookFilterRegistry::class, function () {
            return new HookFilterRegistry;
        });

        // Register async dispatcher only when queue is enabled (HookManager depends on interface, not Laravel)
        if (config('mksine.hooks.queue.enabled', true)) {
            $this->app->singleton(HookAsyncDispatcherInterface::class, LaravelHookAsyncDispatcher::class);
        }

        // Register HookManager as singleton (asyncDispatcher is null when queue disabled)
        $this->app->singleton(HookManager::class, function () {
            $asyncDispatcher = $this->app->bound(HookAsyncDispatcherInterface::class)
                ? $this->app->make(HookAsyncDispatcherInterface::class)
                : null;

            return new HookManager(null, null, null, $asyncDispatcher);
        });

        // Register FormHookManager as singleton
        $this->app->singleton(FormHookManager::class, function () {
            return new FormHookManager;
        });

        // Register TableHookManager as singleton
        $this->app->singleton(TableHookManager::class, function () {
            return new TableHookManager;
        });

        // Register ResourceHookManager as singleton
        $this->app->singleton(ResourceHookManager::class, function () {
            return new ResourceHookManager;
        });

        // Register PageHookManager as singleton
        $this->app->singleton(PageHookManager::class, function () {
            return new PageHookManager;
        });

        // Register PluginLogger as singleton (per-plugin log files)
        $this->app->singleton(PluginLogger::class, function () {
            return new PluginLogger;
        });

        // Register PluginManager as singleton
        $this->app->singleton(PluginManager::class, function () {
            return new PluginManager;
        });

        $this->app->singleton(PluginManifestTranslator::class, function ($app) {
            return new PluginManifestTranslator($app->make('translation.loader'));
        });

        // Register MenuItemSourceManager as singleton
        // Register MenuItemSourceManager as singleton
        $this->app->singleton(MenuItemSourceManager::class, function () {
            return new MenuItemSourceManager;
        });

        // Register MenuLocationManager as singleton
        $this->app->singleton(MenuLocationManager::class, function () {
            return new MenuLocationManager;
        });

        // Register SettingsTabManager for extensible Settings page tabs
        $this->app->singleton(SettingsTabManager::class, function () {
            return new SettingsTabManager;
        });

        // Register MenuService as singleton
        $this->app->singleton(MenuService::class, function () {
            return new MenuService;
        });

        $this->app->singleton(StoreGeoSettings::class);
        $this->app->singleton(GeoResolver::class);

        // Register ThemeManager as singleton
        $this->app->singleton(ThemeManager::class, function () {
            return new ThemeManager;
        });

        // Register ThemeEnqueue as singleton (per-request queue for wp_enqueue_style/script-style API)
        $this->app->singleton(ThemeEnqueue::class, function () {
            return new ThemeEnqueue;
        });

        // Register ThemeRegistry for theme overrides and route callbacks (theme.php API)
        $this->app->singleton(ThemeRegistry::class, function () {
            return new ThemeRegistry;
        });

        // Register ThemeActionManager for template hooks (theme_add_action / theme_do_action)
        $this->app->singleton(ThemeActionManager::class, function () {
            return new ThemeActionManager;
        });

        // Register TranslationFileManager for Languages admin page (edit lang files)
        $this->app->singleton(TranslationFileManager::class, function () {
            return new TranslationFileManager;
        });

        $this->app->singleton(AdminTranslationManager::class, function ($app) {
            return new AdminTranslationManager(
                $app->make(TranslationFileManager::class),
                $app->make(PluginManager::class),
                $app->make(ThemeManager::class),
            );
        });

        // Register ComponentRegistry as singleton for PageBuilder
        $this->app->singleton(ComponentRegistry::class, function () {
            $registry = new ComponentRegistry;

            // Register default components
            $registry->registerMany([
                // Content (Simple)
                HeadingComponent::class,
                TextComponent::class,
                FeatureListComponent::class,

                // Media
                ImageComponent::class,
                SliderComponent::class,

                // Layout
                SpacerComponent::class,
                DividerComponent::class,
                ColumnsComponent::class,
                GridLayoutComponent::class,
                ContainerInsetComponent::class,
                HeroComponent::class,
                TabsComponent::class,

                // Interactive
                ButtonComponent::class,
                CallToActionComponent::class,
                AccordionComponent::class,

                // MKSine theme landing sections
                MksineFinanceShowcaseComponent::class,
                MksineHeroDomainComponent::class,
                MksineServicesTrioComponent::class,
                MksineFeaturedDomainsComponent::class,
                MksineClinicFeaturesGridComponent::class,
                MksineTestimonialsGridComponent::class,
                MksinePostCommentsFeedComponent::class,
            ]);

            return $registry;
        });

        // Register Page Builder Templates
        $this->app->singleton(TemplateRegistry::class, function () {
            $registry = new TemplateRegistry;

            $registry->register('landing-page', LandingPageTemplate::config());
            $registry->register('about-us', AboutPageTemplate::config());
            $registry->register('contact', ContactPageTemplate::config());
            $registry->register('services', ServicesPageTemplate::config());
            $registry->register('blank', BlankTemplate::config());
            $registry->register('mksine-default-home', MksineDefaultHomeTemplate::config());

            return $registry;
        });

        // Register plugin PSR-4 autoload before any service provider boot() so application code
        // (e.g. App\Models\User traits) can reference plugin namespaces. Full initialize() runs
        // later; it also touches the database and must run after the DB layer is ready.
        $this->app->make(PluginManager::class)->registerPluginAutoload();
    }

    public function packageBooted(): void
    {
        LivewireUploadConfiguration::apply();

        MksFilamentDateMacros::register();

        $this->syncAuthUserModelWithMksineConfig();

        // mksine:: lines: Spatie bootPackageTranslations() registers the package path; MksineFileLoader
        // merges lang/{locale}/*.php on top (do not call loadTranslationsFrom(lang_path(), 'mksine') — it
        // replaces the namespace hint and drops package keys).

        $this->registerPublishableLang();
        $this->registerPublishableFonts();
        $this->ensureDefaultLangInProject();

        // Configure Language Switch: locales from TranslationFileManager, render in panel header (topbar).
        if (class_exists(LanguageSwitch::class)) {
            LanguageSwitch::configureUsing(function ($switch) {
                $switch
                    ->locales(fn () => app(TranslationFileManager::class)->getAvailableLocales())
                    ->renderHook(PanelsRenderHook::USER_MENU_BEFORE);
            });
        }

        $this->registerModelPolicies();

        $consoleTerminalRoutes = __DIR__.'/../routes/console-terminal.php';
        if (file_exists($consoleTerminalRoutes)) {
            require $consoleTerminalRoutes;
        }

        $geoRoutes = __DIR__.'/../routes/geo.php';
        if (file_exists($geoRoutes)) {
            require $geoRoutes;
        }

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        // Plugin assets (must run before filament:assets for CLI publish)
        MksinePlugin::registerPluginAssets();

        $this->registerPluginTranslations();
        $this->registerThemeTranslations();

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        FilamentView::registerRenderHook(
            'panels::head.end',
            function (): string {
                return view('mksine::partials.ckeditor-bootstrap')->render();
            }
        );

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__.'/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/mksine/{$file->getFilename()}"),
                ], 'mksine-stubs');
            }
        }

        // Publish migrations directly
        $migrationsPath = __DIR__.'/../database/migrations';
        if (app()->runningInConsole() && file_exists($migrationsPath)) {
            $filesystem = app(Filesystem::class);
            $migrationFiles = $filesystem->files($migrationsPath);

            $publishArray = [];
            foreach ($migrationFiles as $file) {
                $publishArray[$file->getRealPath()] = database_path('migrations/'.$file->getFilename());
            }

            if (! empty($publishArray)) {
                $this->publishes($publishArray, 'mksine-migrations');
            }
        }

        // Register Livewire Components
        Livewire::component('mksine::media-picker-modal', MediaPickerModal::class);

        // Register Frontend Livewire Components
        Livewire::component('mksine::frontend.home', Home::class);
        Livewire::component('mksine::frontend.category-list', CategoryList::class);
        Livewire::component('mksine::frontend.category-show', CategoryShow::class);
        Livewire::component('mksine::frontend.post-list', PostList::class);
        Livewire::component('mksine::frontend.post-show', PostShow::class);
        Livewire::component('mksine::frontend.post-comments', PostComments::class);
        Livewire::component('mksine::frontend.page-show', PageShow::class);
        Livewire::component('mksine::frontend.frontend-resolver', FrontendResolver::class);

        // Register PageBuilder Livewire Components
        Livewire::component('mksine::page-builder', PageBuilder::class);
        Livewire::component('mksine::component-editor', ComponentEditor::class);

        $this->registerThemeLivewireMissingComponentResolver();

        // Register default Menu Item Sources
        $this->registerDefaultMenuItemSources();

        // Register Theme Blade Directives
        ThemeBladeDirectives::register();

        // Register project theme views
        app(ThemeManager::class)->registerProjectThemeViews();

        // Testing
        Testable::mixin(new TestsMksine);

        // Initialize and boot plugins
        $this->initializePluginSystem();

        // Active theme's theme.php (page-builder blocks, menu locations, route callbacks) normally loads
        // from package routes during bootPackageRoutes(). When routes are cached, that file may not run,
        // so components like voltech.special_offers never register and the front shows "unknown component".
        app(ThemeBootstrap::class)->boot();

        // Register default listeners (always available, even without database)
        $this->registerDefaultListeners();

        // Load listeners and hooks from database and register them
        // This will override default listeners if they exist in database
        $this->loadListenersFromDatabase();

        // Fallback: If no form/table hooks in database, discover them from code
        // This ensures hooks work even if mks:discover hasn't been run yet
        $this->discoverFormAndTableHooksFallback();

        $this->registerHttpErrorViews();
    }

    /**
     * Register Laravel Gate policies for package models.
     * Laravel only auto-discovers policies for App\Models\*; package models need explicit binding.
     * Uses app policies (e.g. from Filament Shield) when present.
     */
    protected function registerModelPolicies(): void
    {
        $bindings = [
            Category::class => CategoryPolicy::class,
            Comment::class => CommentPolicy::class,
            Media::class => MediaPolicy::class,
            Menu::class => MenuPolicy::class,
            MenuLocation::class => MenuLocationPolicy::class,
            Page::class => PagePolicy::class,
            Post::class => PostPolicy::class,
            Role::class => RolePolicy::class,
            GeoCountry::class => GeoCountryPolicy::class,
            GeoState::class => GeoStatePolicy::class,
            GeoCity::class => GeoCityPolicy::class,
        ];

        foreach ($bindings as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Theme Livewire classes use PSR-4 under e.g. Themes\{Theme}\Livewire\* (see ThemeBootstrap).
     * Livewire maps dotted names from the FQCN, but reverse resolution prepends
     * config('livewire.class_namespace') (App\Livewire), so updates after the first request fail.
     * Map names starting with "themes." back to the real class.
     */
    protected function registerThemeLivewireMissingComponentResolver(): void
    {
        Livewire::resolveMissingComponent(
            fn (string $name): ?string => ThemeLivewireMissingComponentResolver::resolve($name)
        );
    }

    /**
     * Align Laravel's auth provider and Filament Shield with mksine.user_model so
     * consuming apps do not have to repeat the same FQCN in config/auth.php and .env.
     * Runs before plugin boot; plugins may override (e.g. mks-booking user subclass).
     */
    protected function syncAuthUserModelWithMksineConfig(): void
    {
        if (! (bool) config('mksine.sync_auth_user_model', true)) {
            return;
        }

        $model = config('mksine.user_model');

        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return;
        }

        config([
            'auth.providers.users.model' => $model,
            'filament-shield.auth_provider_model' => $model,
        ]);
    }

    /**
     * Register default menu item sources.
     */
    protected function registerDefaultMenuItemSources(): void
    {
        $sourceManager = app(MenuItemSourceManager::class);

        // Register core sources
        $sourceManager->register('custom_link', new CustomLinkMenuItemSource);
        $sourceManager->register('category', new CategoryMenuItemSource);
        $sourceManager->register('page', new PageMenuItemSource);
        $sourceManager->register('post', new PostMenuItemSource);
    }

    /**
     * Initialize and boot the plugin system.
     */
    protected function initializePluginSystem(): void
    {
        try {
            $pluginManager = app(PluginManager::class);

            // Check for plugins that crashed during previous boot
            $pluginManager->checkBootFailures();

            // Initialize plugin system (discovery, autoloading)
            $pluginManager->initialize();

            // Boot all active plugins
            $pluginManager->bootPlugins();
        } catch (\Exception $e) {
            // Plugin system failure should not crash the entire application
            // Log the error and continue
            if (app()->bound('log')) {
                Log::error('Plugin system initialization failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'miran/mksine';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [];

        $cssPath = __DIR__.'/../resources/dist/mksine.css';
        $jsPath = __DIR__.'/../resources/dist/mksine.js';
        $ckeditorCssPath = __DIR__.'/../resources/dist/mks-ckeditor-field.css';
        $ckeditorFilamentCssPath = __DIR__.'/../resources/css/mks-ckeditor-filament-overrides.css';
        $ckeditorJsPath = __DIR__.'/../resources/dist/mks-ckeditor-field.js';

        if (file_exists($ckeditorCssPath)) {
            $assets[] = Css::make('mks-ckeditor-field', $ckeditorCssPath);
        }

        if (file_exists($ckeditorFilamentCssPath)) {
            $assets[] = Css::make('mks-ckeditor-filament', $ckeditorFilamentCssPath);
        }

        if (file_exists($ckeditorJsPath)) {
            $assets[] = Js::make('mks-ckeditor-field', $ckeditorJsPath);
        }

        if (file_exists($cssPath)) {
            // Loaded via PanelsRenderHook::STYLES_AFTER so panel theme.css cannot
            // override our Tailwind dark: utilities (bg-white wins when theme loads last).
            $assets[] = Css::make('mksine-styles', $cssPath)->loadedOnRequest();
        }

        if (file_exists($jsPath)) {
            $assets[] = Js::make('mksine-scripts', $jsPath);
        }

        $consoleTerminalJs = __DIR__.'/../resources/js/admin-console-terminal.js';
        if (file_exists($consoleTerminalJs)) {
            $assets[] = Js::make('mksine-console-terminal', $consoleTerminalJs);
        }

        return $assets;
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            MksineCommand::class,
            ArtisanCommand::class,
            MksineInstallCommand::class,
            DiscoverHooksCommand::class,
            GeoImportCommand::class,
            GeoMigrateLegacyIranCommand::class,
            GeoSyncCityNativeNamesCommand::class,
            // Plugin commands
            PluginListCommand::class,
            PluginInstallCommand::class,
            PluginMigrateCommand::class,
            PluginActivateCommand::class,
            PluginDeactivateCommand::class,
            PluginUninstallCommand::class,
            PluginDiscoverCommand::class,
            PluginPublishCommand::class,
            PluginPublishLangCommand::class,
            PluginMakeCommand::class,
            PluginMakeModelCommand::class,
            PluginMakeResourceCommand::class,
            PluginMakePageCommand::class,
            PluginMakeWidgetCommand::class,
            // Theme commands
            ThemeMakeCommand::class,
            ThemePublishCommand::class,
            ThemePublishLangCommand::class,
            // Updater commands (ZIP-based plugin / theme / core updates)
            UpdatePluginCommand::class,
            UpdateThemeCommand::class,
            UpdateCoreCommand::class,
            RollbackPluginCommand::class,
            RollbackThemeCommand::class,
            RollbackCoreCommand::class,
            ReleaseArchiveCommand::class,
            ConsoleRunDetachedCommand::class,
            CreateSuperAdminCommand::class,
            FreshSuperAdminCommand::class,
            MigrateSmartCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_media_table',
            'create_media_attachments_table',
            'create_mks_hooks_table',
            'create_posts_table',
            'create_categories_table',
            'create_category_post_table',
        ];
    }

    /**
     * Load listeners and hooks from database and register them.
     * Only registers items that exist in database, skipping default listeners
     * to prevent duplicate registration.
     */
    protected function loadListenersFromDatabase(): void
    {
        try {
            // Check if database connection is available and table exists
            if (! app()->bound('db') || ! Schema::hasTable('mks_hooks')) {
                return;
            }

            $hookManager = app(HookManager::class);
            $formHookManager = app(FormHookManager::class);
            $tableHookManager = app(TableHookManager::class);

            // Get all registered listeners to check for duplicates
            $registeredListeners = $hookManager->getRegisteredListeners();

            $hooks = DB::table('mks_hooks')
                ->where('is_enabled', true)
                ->orderBy('priority')
                ->get();

            foreach ($hooks as $hook) {
                $listenerClass = $hook->listener_class;
                $hookType = $hook->hook_type ?? 'event'; // Default to 'event' for backward compatibility

                // Skip if listener class doesn't exist
                if (! class_exists($listenerClass)) {
                    continue;
                }

                switch ($hookType) {
                    case 'event':
                        $this->loadEventListener($hook, $hookManager, $registeredListeners);

                        break;
                    case 'form':
                        $this->loadFormHook($hook, $formHookManager);

                        break;
                    case 'table':
                        $this->loadTableHook($hook, $tableHookManager);

                        break;
                }
            }
        } catch (\Exception $e) {
            // Log warning instead of silent failure
            // This allows package to boot while providing visibility into issues
            if (app()->bound('log') && ! $this->isDatabaseBootstrapping($e)) {
                Log::warning('MKS CMS: Failed to load hooks from database', [
                    'error' => $e->getMessage(),
                    'context' => 'loadListenersFromDatabase',
                ]);
            }
        }
    }

    /**
     * Check if exception is due to database bootstrapping (expected during migrations).
     */
    private function registerHttpErrorViews(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'renderable')) {
                return;
            }

            $handler->renderable(function (\Throwable $e, $request) {
                if (! $e instanceof HttpExceptionInterface || $e->getStatusCode() !== 403) {
                    return null;
                }

                if (is_file(resource_path('views/errors/403.blade.php'))) {
                    return null;
                }

                return response()->view('mksine::errors.403', [
                    'exception' => $e,
                    'message' => $e->getMessage(),
                    'currentUrl' => $request->fullUrl(),
                ], 403, $e->getHeaders());
            });
        });
    }

    private function isDatabaseBootstrapping(\Exception $e): bool
    {
        $bootstrappingMessages = [
            'SQLSTATE[HY000]',
            'no such table',
            'Base table or view not found',
            'Unknown database',
            'Connection refused',
            'could not find driver',
        ];

        $message = $e->getMessage();
        foreach ($bootstrappingMessages as $bootstrapMessage) {
            if (str_contains($message, $bootstrapMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load event listener from database.
     *
     * @param  object  $hook
     */
    private function loadEventListener($hook, HookManager $hookManager, array $registeredListeners): void
    {
        $eventName = $hook->event_name;
        $listenerClass = $hook->listener_class;

        // Check if this listener is already registered for this event
        $alreadyRegistered = false;
        if (isset($registeredListeners[$eventName])) {
            foreach ($registeredListeners[$eventName] as $registered) {
                if ($registered['listener'] === $listenerClass) {
                    $alreadyRegistered = true;

                    break;
                }
            }
        }

        // Skip if already registered (e.g. from code). Priority and enabled state
        // still come from HookStateRepository + mks_hooks at dispatch time.
        if ($alreadyRegistered) {
            return;
        }

        $hookManager->register(
            $eventName,
            $listenerClass,
            $hook->priority
        );
    }

    /**
     * Load form hook from database.
     *
     * @param  object  $hook
     */
    private function loadFormHook($hook, FormHookManager $formHookManager): void
    {
        $formName = $hook->hook_name;
        $listenerClass = $hook->listener_class;

        if ($formName && class_exists($listenerClass)) {
            $formHookManager->extend($formName, [$listenerClass, 'extend']);
        }
    }

    /**
     * Load table hook from database.
     *
     * @param  object  $hook
     */
    private function loadTableHook($hook, TableHookManager $tableHookManager): void
    {
        $tableName = $hook->hook_name;
        $listenerClass = $hook->listener_class;

        if ($tableName && class_exists($listenerClass)) {
            $tableHookManager->extend($tableName, [$listenerClass, 'extend']);
        }
    }

    /**
     * Register default listeners for the package.
     * These listeners are registered even if database is not ready.
     */
    protected function registerDefaultListeners(): void
    {
        // Default listeners can be registered here if needed
        // Form and table hooks are automatically discovered via discoverFormAndTableHooksFallback()
    }

    /**
     * Fallback: Discover and register form/table hooks from code if not in database.
     * This ensures hooks work even if mks:discover hasn't been run yet.
     */
    protected function discoverFormAndTableHooksFallback(): void
    {
        try {
            // Check if database is available
            if (! app()->bound('db') || ! Schema::hasTable('mks_hooks')) {
                // If no database, discover from code
                $this->discoverFormAndTableHooks();

                return;
            }

            $formHookManager = app(FormHookManager::class);
            $tableHookManager = app(TableHookManager::class);

            // Check if hooks are already registered (from database)
            $formHooksInDb = DB::table('mks_hooks')
                ->where('hook_type', 'form')
                ->where('is_enabled', true)
                ->count();
            $tableHooksInDb = DB::table('mks_hooks')
                ->where('hook_type', 'table')
                ->where('is_enabled', true)
                ->count();

            // If no hooks in database, discover from code as fallback
            if ($formHooksInDb === 0 && $tableHooksInDb === 0) {
                $this->discoverFormAndTableHooks();
            }
        } catch (\Exception $e) {
            // Log warning for unexpected database errors
            if (app()->bound('log') && ! $this->isDatabaseBootstrapping($e)) {
                Log::warning('MKS CMS: Database error during hook discovery fallback', [
                    'error' => $e->getMessage(),
                    'context' => 'discoverFormAndTableHooksFallback',
                ]);
            }

            // If database error, discover from code as fallback
            $this->discoverFormAndTableHooks();
        }
    }

    /**
     * Discover and register form/table hooks automatically.
     * This method discovers classes implementing FormHookListenerInterface and TableHookListenerInterface.
     */
    protected function discoverFormAndTableHooks(): void
    {
        $listenersPath = __DIR__.'/Core/Listeners';

        if (! is_dir($listenersPath)) {
            return;
        }

        $formHookManager = app(FormHookManager::class);
        $tableHookManager = app(TableHookManager::class);

        // Discover form hooks
        $formHooks = $this->discoverFormHooks($listenersPath);
        foreach ($formHooks as $formName => $listenerClass) {
            $formHookManager->extend($formName, [$listenerClass, 'extend']);
        }

        // Discover table hooks
        $tableHooks = $this->discoverTableHooks($listenersPath);
        foreach ($tableHooks as $tableName => $listenerClass) {
            $tableHookManager->extend($tableName, [$listenerClass, 'extend']);
        }
    }

    /**
     * Discover form hook listeners in the given directory.
     *
     * @return array<string, string> Array of [formName => listenerClass]
     */
    private function discoverFormHooks(string $path): array
    {
        $hooks = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        $phpFiles = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        $fileCount = 0;
        foreach ($phpFiles as $file) {
            $fileCount++;
            $filePath = $file[0];
            $className = $this->getClassNameFromFile($filePath);
            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);
            if (! $fullClassName || ! class_exists($fullClassName)) {
                continue;
            }

            $reflection = new \ReflectionClass($fullClassName);
            if ($reflection->implementsInterface(FormHookListenerInterface::class)) {
                try {
                    $formName = $fullClassName::getFormName();
                    $hooks[$formName] = $fullClassName;
                } catch (\Exception $e) {
                    // Skip if getFormName() fails
                }
            }
        }

        return $hooks;
    }

    /**
     * Discover table hook listeners in the given directory.
     *
     * @return array<string, string> Array of [tableName => listenerClass]
     */
    private function discoverTableHooks(string $path): array
    {
        $hooks = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        $phpFiles = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        $fileCount = 0;
        foreach ($phpFiles as $file) {
            $fileCount++;
            $filePath = $file[0];
            $className = $this->getClassNameFromFile($filePath);
            if (! $className) {
                continue;
            }

            $fullClassName = $this->getFullClassName($filePath, $className);
            if (! $fullClassName || ! class_exists($fullClassName)) {
                continue;
            }

            $reflection = new \ReflectionClass($fullClassName);
            if ($reflection->implementsInterface(TableHookListenerInterface::class)) {
                try {
                    $tableName = $fullClassName::getTableName();
                    $hooks[$tableName] = $fullClassName;
                } catch (\Exception $e) {
                    // Skip if getTableName() fails
                }
            }
        }

        return $hooks;
    }

    /**
     * Get class name from PHP file.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        // Match class declaration, but exclude comments and strings
        // Pattern: class ClassName (with optional extends/implements)
        // Must be on its own line or after namespace/use statements
        if (preg_match('/^\s*class\s+(\w+)(?:\s+extends|\s+implements|\s*\{|\s*$)/m', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get full class name from file path.
     */
    private function getFullClassName(string $filePath, string $className): ?string
    {
        $content = file_get_contents($filePath);

        // Try to match namespace (handle both with and without declare)
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $fullClassName = $matches[1].'\\'.$className;

            // Verify class exists
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }

            // Try to autoload
            if (spl_autoload_functions()) {
                spl_autoload_call($fullClassName);
                if (class_exists($fullClassName)) {
                    return $fullClassName;
                }
            }
        }

        return null;
    }

    /**
     * Load translations for active plugins from lang/vendor/{plugin-id}/.
     */
    private function registerPluginTranslations(): void
    {
        if (! function_exists('lang_path')) {
            return;
        }

        try {
            if (! Schema::hasTable('mks_plugins')) {
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

                $publishedPath = lang_path('vendor/'.$pluginId);
                if (is_dir($publishedPath)) {
                    $this->loadTranslationsFrom($publishedPath, $pluginId);
                    $this->clearPoisonedNamespaceTranslationCache($pluginId);
                } elseif ($manifest->translationsPath()) {
                    $this->loadTranslationsFrom($manifest->translationsPath(), $pluginId);
                    $this->clearPoisonedNamespaceTranslationCache($pluginId);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if DB not ready (e.g. during migrate)
        }
    }

    /**
     * Early boot can resolve {@see __()} for a plugin namespace before {@see loadTranslationsFrom()}
     * registers hints; Laravel's translator caches an empty group forever. Drop poisoned cache entries
     * after namespaces are registered so the next lookup reloads from disk.
     */
    private function clearPoisonedNamespaceTranslationCache(string $namespace): void
    {
        $translator = $this->app->make('translator');

        if (! method_exists($translator, 'setLoaded')) {
            return;
        }

        $reflection = new \ReflectionClass($translator);
        $property = $reflection->getProperty('loaded');
        $property->setAccessible(true);

        /** @var array<string, mixed> $loaded */
        $loaded = $property->getValue($translator);

        if (! array_key_exists($namespace, $loaded)) {
            return;
        }

        unset($loaded[$namespace]);

        $translator->setLoaded($loaded);
    }

    /**
     * Load translations for the active theme from lang/vendor/theme-{identifier}/.
     */
    private function registerThemeTranslations(): void
    {
        if (! function_exists('lang_path')) {
            return;
        }

        try {
            $themeManager = app(ThemeManager::class);
            $active = $themeManager->getActive();

            if (! $active) {
                return;
            }

            $publishedPath = lang_path('vendor/theme-'.$active->identifier);
            if (is_dir($publishedPath)) {
                $this->loadTranslationsFrom($publishedPath, 'theme-'.$active->identifier);
            } else {
                $src = $themeManager->getThemeTranslationsPath($active);
                if ($src) {
                    $this->loadTranslationsFrom($src, 'theme-'.$active->identifier);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if theme system not ready
        }
    }

    /**
     * Register publishable font files: package fonts → public/fonts.
     * Run: php artisan vendor:publish --tag=mksine-fonts
     */
    private function registerPublishableFonts(): void
    {
        $fontsPath = realpath(__DIR__.'/../resources/fonts/iranyekan');
        if (! $fontsPath || ! is_dir($fontsPath)) {
            return;
        }

        $publishArray = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fontsPath, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $relative = FilesystemPath::relativeTo($fontsPath, $file->getPathname());
                $publishArray[$file->getPathname()] = public_path('fonts/iranyekan/'.$relative);
            }
        }

        if ($publishArray !== []) {
            $this->publishes($publishArray, 'mksine-fonts');
        }
    }

    /**
     * Register publishable translation files: package lang → project lang path.
     * Run: php artisan vendor:publish --tag=mksine-lang
     */
    private function registerPublishableLang(): void
    {
        if (! function_exists('lang_path')) {
            return;
        }

        $packageLang = realpath(__DIR__.'/../resources/lang');
        if (! $packageLang || ! is_dir($packageLang)) {
            return;
        }

        $publishArray = [];
        foreach (
            array_merge(
                glob($packageLang.'/*/*.php') ?: [],
                glob($packageLang.'/*.json') ?: []
            ) as $absPath
        ) {
            $relative = FilesystemPath::relativeTo($packageLang, $absPath);
            $publishArray[$absPath] = lang_path($relative);
        }

        if ($publishArray !== []) {
            $this->publishes($publishArray, 'mksine-lang');
        }
    }

    /**
     * Copy default package translations to project lang path if missing (e.g. after install).
     * Only copies when the target file does not exist so user edits are not overwritten.
     */
    private function ensureDefaultLangInProject(): void
    {
        if (! function_exists('lang_path')) {
            return;
        }

        $packageLang = realpath(__DIR__.'/../resources/lang');
        if (! $packageLang || ! is_dir($packageLang)) {
            return;
        }

        $filesystem = app(Filesystem::class);
        $candidates = array_merge(
            glob($packageLang.'/*/*.php') ?: [],
            glob($packageLang.'/*.json') ?: []
        );
        foreach ($candidates as $absPath) {
            $relative = FilesystemPath::relativeTo($packageLang, $absPath);
            $target = lang_path($relative);
            if (! $filesystem->exists($target)) {
                $filesystem->ensureDirectoryExists(dirname($target));
                $filesystem->copy($absPath, $target);
            }
        }
    }
}
