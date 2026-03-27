<?php

namespace Miran\Mksine;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Miran\Mksine\Commands\ArtisanCommand;
use Miran\Mksine\Commands\MksineCommand;
use Miran\Mksine\Commands\MksineInstallCommand;
use Miran\Mksine\Console\Commands\DiscoverHooksCommand;
use Miran\Mksine\Console\Commands\PluginActivateCommand;
use Miran\Mksine\Console\Commands\PluginDeactivateCommand;
use Miran\Mksine\Console\Commands\PluginDiscoverCommand;
use Miran\Mksine\Console\Commands\PluginInstallCommand;
use Miran\Mksine\Console\Commands\PluginMigrateCommand;
use Miran\Mksine\Console\Commands\PluginListCommand;
use Miran\Mksine\Console\Commands\PluginMakeCommand;
use Miran\Mksine\Console\Commands\PluginMakeModelCommand;
use Miran\Mksine\Console\Commands\PluginMakePageCommand;
use Miran\Mksine\Console\Commands\PluginMakeResourceCommand;
use Miran\Mksine\Console\Commands\PluginMakeWidgetCommand;
use Miran\Mksine\Console\Commands\PluginPublishCommand;
use Miran\Mksine\Console\Commands\PluginPublishLangCommand;
use Miran\Mksine\Console\Commands\PluginUninstallCommand;
use Miran\Mksine\Console\Commands\ThemeMakeCommand;
use Miran\Mksine\Console\Commands\ThemePublishCommand;
use Miran\Mksine\Console\Commands\ThemePublishLangCommand;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\MenuItemSourceManager;
use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Core\Hooks\HookAsyncDispatcherInterface;
use Miran\Mksine\Core\Hooks\LaravelHookAsyncDispatcher;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Core\Hooks\SettingsTabManager;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Core\MenuItemSources\CategoryMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\CustomLinkMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\PageMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\PostMenuItemSource;
use Miran\Mksine\Core\Plugins\PluginLogger;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Theme\ThemeBladeDirectives;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Livewire\MediaPickerModal;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\AccordionComponent;
use Miran\Mksine\Core\PageBuilder\Components\ButtonComponent;
use Miran\Mksine\Core\PageBuilder\Components\CallToActionComponent;
use Miran\Mksine\Core\PageBuilder\Components\ColumnsComponent;
use Miran\Mksine\Core\PageBuilder\Components\DividerComponent;
use Miran\Mksine\Core\PageBuilder\Components\FeatureListComponent;
use Miran\Mksine\Core\PageBuilder\Components\HeadingComponent;
use Miran\Mksine\Core\PageBuilder\Components\HeroComponent;
use Miran\Mksine\Core\PageBuilder\Components\ImageComponent;
use Miran\Mksine\Core\PageBuilder\Components\SliderComponent;
use Miran\Mksine\Core\PageBuilder\Components\SpacerComponent;
use Miran\Mksine\Core\PageBuilder\Components\TabsComponent;
use Miran\Mksine\Core\PageBuilder\Components\TestimonialComponent;
use Miran\Mksine\Core\PageBuilder\Components\TextComponent;
use Miran\Mksine\Core\PageBuilder\Livewire\ComponentEditor;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;
use Miran\Mksine\Core\PageBuilder\TemplateRegistry;
use Miran\Mksine\Core\PageBuilder\Templates\AboutPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\BlankTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\ContactPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\LandingPageTemplate;
use Miran\Mksine\Core\PageBuilder\Templates\ServicesPageTemplate;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Services\MenuService;
use Miran\Mksine\Testing\TestsMksine;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Permission\Models\Role;

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
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('miransalehi/mksine');
            })
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
        require_once __DIR__ . '/Helpers/functions.php';

        $this->app->singleton(Mksine::class, function () {
            return new Mksine;
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

        // Register ThemeManager as singleton
        $this->app->singleton(ThemeManager::class, function () {
            return new ThemeManager;
        });

        // Register ThemeEnqueue as singleton (per-request queue for wp_enqueue_style/script-style API)
        $this->app->singleton(\Miran\Mksine\Core\Theme\ThemeEnqueue::class, function () {
            return new \Miran\Mksine\Core\Theme\ThemeEnqueue;
        });

        // Register ThemeRegistry for theme overrides and route callbacks (theme.php API)
        $this->app->singleton(\Miran\Mksine\Core\Theme\ThemeRegistry::class, function () {
            return new \Miran\Mksine\Core\Theme\ThemeRegistry;
        });

        // Register ThemeActionManager for template hooks (theme_add_action / theme_do_action)
        $this->app->singleton(\Miran\Mksine\Core\Theme\ThemeActionManager::class, function () {
            return new \Miran\Mksine\Core\Theme\ThemeActionManager;
        });

        // Register TranslationFileManager for Languages admin page (edit lang files)
        $this->app->singleton(\Miran\Mksine\Core\Translation\TranslationFileManager::class, function () {
            return new \Miran\Mksine\Core\Translation\TranslationFileManager;
        });

        $this->app->singleton(\Miran\Mksine\Core\Translation\AdminTranslationManager::class, function ($app) {
            return new \Miran\Mksine\Core\Translation\AdminTranslationManager(
                $app->make(\Miran\Mksine\Core\Translation\TranslationFileManager::class),
                $app->make(\Miran\Mksine\Core\Plugins\PluginManager::class),
                $app->make(\Miran\Mksine\Core\Theme\ThemeManager::class),
            );
        });

        // Register ComponentRegistry as singleton for PageBuilder
        $this->app->singleton(ComponentRegistry::class, function () {
            $registry = new ComponentRegistry();

            // Register default components
            $registry->registerMany([
                // Content (Simple)
                HeadingComponent::class,
                TextComponent::class,
                FeatureListComponent::class,
                TestimonialComponent::class,

                // Media
                ImageComponent::class,
                SliderComponent::class,

                // Layout
                SpacerComponent::class,
                DividerComponent::class,
                ColumnsComponent::class,
                HeroComponent::class,
                TabsComponent::class,

                // Interactive
                ButtonComponent::class,
                CallToActionComponent::class,
                AccordionComponent::class,
            ]);

            return $registry;
        });

        // Register Page Builder Templates
        $this->app->singleton(TemplateRegistry::class, function () {
            $registry = new TemplateRegistry();

            $registry->register('landing-page', LandingPageTemplate::config());
            $registry->register('about-us', AboutPageTemplate::config());
            $registry->register('contact', ContactPageTemplate::config());
            $registry->register('services', ServicesPageTemplate::config());
            $registry->register('blank', BlankTemplate::config());

            return $registry;
        });

        // Register plugin PSR-4 autoload before any service provider boot() so application code
        // (e.g. App\Models\User traits) can reference plugin namespaces. Full initialize() runs
        // later; it also touches the database and must run after the DB layer is ready.
        $this->app->make(PluginManager::class)->registerPluginAutoload();
    }

    public function packageBooted(): void
    {
        // Load package defaults first, then project lang so project overrides (Languages page edits project files).
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'mksine');
        if (function_exists('lang_path') && is_dir(lang_path())) {
            $this->loadTranslationsFrom(lang_path(), 'mksine');
        }

        $this->registerPublishableLang();
        $this->registerPublishableFonts();
        $this->ensureDefaultLangInProject();

        // Configure Language Switch: locales from TranslationFileManager, render in panel header (topbar).
        if (class_exists(\BezhanSalleh\LanguageSwitch\LanguageSwitch::class)) {
            \BezhanSalleh\LanguageSwitch\LanguageSwitch::configureUsing(function ($switch) {
                $switch
                    ->locales(fn () => app(\Miran\Mksine\Core\Translation\TranslationFileManager::class)->getAvailableLocales())
                    ->renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE);
            });
        }

        $this->registerModelPolicies();

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
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/mksine/{$file->getFilename()}"),
                ], 'mksine-stubs');
            }
        }

        // Publish migrations directly
        $migrationsPath = __DIR__ . '/../database/migrations';
        if (app()->runningInConsole() && file_exists($migrationsPath)) {
            $filesystem = app(Filesystem::class);
            $migrationFiles = $filesystem->files($migrationsPath);

            $publishArray = [];
            foreach ($migrationFiles as $file) {
                $publishArray[$file->getRealPath()] = database_path('migrations/' . $file->getFilename());
            }

            if (! empty($publishArray)) {
                $this->publishes($publishArray, 'mksine-migrations');
            }
        }

        // Register Livewire Components
        Livewire::component('mksine::media-picker-modal', MediaPickerModal::class);
        
        // Register Frontend Livewire Components
        Livewire::component('mksine::frontend.home', \Miran\Mksine\Livewire\Frontend\Home::class);
        Livewire::component('mksine::frontend.category-list', \Miran\Mksine\Livewire\Frontend\CategoryList::class);
        Livewire::component('mksine::frontend.category-show', \Miran\Mksine\Livewire\Frontend\CategoryShow::class);
        Livewire::component('mksine::frontend.post-list', \Miran\Mksine\Livewire\Frontend\PostList::class);
        Livewire::component('mksine::frontend.post-show', \Miran\Mksine\Livewire\Frontend\PostShow::class);
        Livewire::component('mksine::frontend.post-comments', \Miran\Mksine\Livewire\Frontend\PostComments::class);
        Livewire::component('mksine::frontend.page-show', \Miran\Mksine\Livewire\Frontend\PageShow::class);
        Livewire::component('mksine::frontend.frontend-resolver', \Miran\Mksine\Livewire\Frontend\FrontendResolver::class);

        // Register PageBuilder Livewire Components
        Livewire::component('mksine::page-builder', PageBuilder::class);
        Livewire::component('mksine::component-editor', ComponentEditor::class);

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

        // Register default listeners (always available, even without database)
        $this->registerDefaultListeners();

        // Load listeners and hooks from database and register them
        // This will override default listeners if they exist in database
        $this->loadListenersFromDatabase();

        // Fallback: If no form/table hooks in database, discover them from code
        // This ensures hooks work even if mks:discover hasn't been run yet
        $this->discoverFormAndTableHooksFallback();
    }

    /**
     * Register Laravel Gate policies for package models.
     * Laravel only auto-discovers policies for App\Models\*; package models need explicit binding.
     * Uses app policies (e.g. from Filament Shield) when present.
     */
    protected function registerModelPolicies(): void
    {
        $bindings = [
            Category::class => \App\Policies\CategoryPolicy::class,
            Comment::class => \App\Policies\CommentPolicy::class,
            Media::class => \App\Policies\MediaPolicy::class,
            Menu::class => \App\Policies\MenuPolicy::class,
            MenuLocation::class => \App\Policies\MenuLocationPolicy::class,
            Page::class => \App\Policies\PagePolicy::class,
            Post::class => \App\Policies\PostPolicy::class,
            Role::class => \App\Policies\RolePolicy::class,
        ];

        foreach ($bindings as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
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
                \Illuminate\Support\Facades\Log::error('Plugin system initialization failed', [
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

        $cssPath = __DIR__ . '/../resources/dist/mksine.css';
        $jsPath = __DIR__ . '/../resources/dist/mksine.js';
        $ckeditorCssPath = __DIR__ . '/../resources/dist/mks-ckeditor-field.css';
        $ckeditorJsPath = __DIR__ . '/../resources/dist/mks-ckeditor-field.js';

        if (file_exists($ckeditorCssPath)) {
            $assets[] = Css::make('mks-ckeditor-field', $ckeditorCssPath);
        }

        if (file_exists($ckeditorJsPath)) {
            $assets[] = Js::make('mks-ckeditor-field', $ckeditorJsPath);
        }

        if (file_exists($cssPath)) {
            $assets[] = Css::make('mksine-styles', $cssPath);
        }

        if (file_exists($jsPath)) {
            $assets[] = Js::make('mksine-scripts', $jsPath);
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
                \Illuminate\Support\Facades\Log::warning('MKS CMS: Failed to load hooks from database', [
                    'error' => $e->getMessage(),
                    'context' => 'loadListenersFromDatabase',
                ]);
            }
        }
    }

    /**
     * Check if exception is due to database bootstrapping (expected during migrations).
     */
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

        // Skip if already registered (from default listeners)
        if ($alreadyRegistered) {
            // Just update priority and state if needed
            if ($hook->priority !== 0) {
                $hookManager->setPriority($listenerClass, $hook->priority);
            }
            if (! $hook->is_enabled) {
                $hookManager->disableListener($listenerClass);
            }

            return;
        }

        // Register new listener from database
        $hookManager->register(
            $eventName,
            $listenerClass,
            $hook->priority
        );

        // Apply state management if needed
        if (! $hook->is_enabled) {
            $hookManager->disableListener($listenerClass);
        }

        // Apply priority override if different from default
        if ($hook->priority !== 0) {
            $hookManager->setPriority($listenerClass, $hook->priority);
        }
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
                \Illuminate\Support\Facades\Log::warning('MKS CMS: Database error during hook discovery fallback', [
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
        $listenersPath = __DIR__ . '/Core/Listeners';

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
            if ($reflection->implementsInterface(\Miran\Mksine\Core\Hooks\FormHookListenerInterface::class)) {
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
            if ($reflection->implementsInterface(\Miran\Mksine\Core\Hooks\TableHookListenerInterface::class)) {
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
            $fullClassName = $matches[1] . '\\' . $className;

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

                $publishedPath = lang_path('vendor/' . $pluginId);
                if (is_dir($publishedPath)) {
                    $this->loadTranslationsFrom($publishedPath, $pluginId);
                } elseif ($manifest->translationsPath()) {
                    $this->loadTranslationsFrom($manifest->translationsPath(), $pluginId);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if DB not ready (e.g. during migrate)
        }
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
            $themeManager = app(\Miran\Mksine\Core\Theme\ThemeManager::class);
            $active = $themeManager->getActive();

            if (! $active) {
                return;
            }

            $publishedPath = lang_path('vendor/theme-' . $active->identifier);
            if (is_dir($publishedPath)) {
                $this->loadTranslationsFrom($publishedPath, 'theme-' . $active->identifier);
            } else {
                $src = $themeManager->getThemeTranslationsPath($active);
                if ($src) {
                    $this->loadTranslationsFrom($src, 'theme-' . $active->identifier);
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
        $fontsPath = realpath(__DIR__ . '/../resources/fonts/iranyekan');
        if (! $fontsPath || ! is_dir($fontsPath)) {
            return;
        }

        $publishArray = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fontsPath, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $relative = str_replace($fontsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $publishArray[$file->getPathname()] = public_path('fonts/iranyekan/' . $relative);
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

        $packageLang = realpath(__DIR__ . '/../resources/lang');
        if (! $packageLang || ! is_dir($packageLang)) {
            return;
        }

        $publishArray = [];
        foreach (array_merge(
            glob($packageLang . '/*/*.php') ?: [],
            glob($packageLang . '/*.json') ?: []
        ) as $absPath) {
            $relative = str_replace($packageLang . DIRECTORY_SEPARATOR, '', $absPath);
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

        $packageLang = realpath(__DIR__ . '/../resources/lang');
        if (! $packageLang || ! is_dir($packageLang)) {
            return;
        }

        $filesystem = app(Filesystem::class);
        $candidates = array_merge(
            glob($packageLang . '/*/*.php') ?: [],
            glob($packageLang . '/*.json') ?: []
        );
        foreach ($candidates as $absPath) {
            $relative = str_replace($packageLang . DIRECTORY_SEPARATOR, '', $absPath);
            $target = lang_path($relative);
            if (! $filesystem->exists($target)) {
                $filesystem->ensureDirectoryExists(dirname($target));
                $filesystem->copy($absPath, $target);
            }
        }
    }
}
