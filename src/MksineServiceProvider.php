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
use Miran\Mksine\Console\Commands\PluginListCommand;
use Miran\Mksine\Console\Commands\PluginMakeCommand;
use Miran\Mksine\Console\Commands\PluginMakePageCommand;
use Miran\Mksine\Console\Commands\PluginMakeResourceCommand;
use Miran\Mksine\Console\Commands\PluginMakeWidgetCommand;
use Miran\Mksine\Console\Commands\PluginUninstallCommand;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\MenuItemSourceManager;
use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Core\MenuItemSources\CategoryMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\CustomLinkMenuItemSource;
use Miran\Mksine\Core\MenuItemSources\PostMenuItemSource;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Livewire\MediaPickerModal;
use Miran\Mksine\Services\MenuService;
use Miran\Mksine\Testing\TestsMksine;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

        // Register HookManager as singleton
        $this->app->singleton(HookManager::class, function () {
            return new HookManager;
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

        // Register MenuService as singleton
        $this->app->singleton(MenuService::class, function () {
            return new MenuService;
        });
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        FilamentView::registerRenderHook(
            'panels::head.end',
            function (): string {
                return <<<'HTML'
                    <script>
                        window.ckeditorInstances = window.ckeditorInstances || {};
                    </script>
                HTML;
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

        // Register default Menu Item Sources
        $this->registerDefaultMenuItemSources();

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
     * Register default menu item sources.
     */
    protected function registerDefaultMenuItemSources(): void
    {
        $sourceManager = app(MenuItemSourceManager::class);

        // Register core sources
        $sourceManager->register('custom_link', new CustomLinkMenuItemSource);
        $sourceManager->register('category', new CategoryMenuItemSource);
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
            PluginActivateCommand::class,
            PluginDeactivateCommand::class,
            PluginUninstallCommand::class,
            PluginDiscoverCommand::class,
            PluginMakeCommand::class,
            PluginMakeResourceCommand::class,
            PluginMakePageCommand::class,
            PluginMakeWidgetCommand::class,
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
}
