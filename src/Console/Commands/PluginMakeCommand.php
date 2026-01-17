<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Core\Plugins\PluginDiscovery;

class PluginMakeCommand extends Command
{
    protected $signature = 'mks-plugin:make 
                            {name : The plugin name (e.g., my-custom-plugin)}
                            {--namespace= : The PHP namespace (defaults to StudlyCase of name)}
                            {--author= : The plugin author}
                            {--description= : The plugin description}';

    protected $description = 'Create a new MKS CMS plugin scaffold';

    private Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');

        // Validate name format
        if (! preg_match('/^[a-z0-9\-]+$/', $name)) {
            $this->error('Plugin name must be lowercase alphanumeric with hyphens only.');
            $this->line('Example: my-custom-plugin');

            return self::FAILURE;
        }

        $pluginPath = base_path("plugins/{$name}");

        if ($this->files->isDirectory($pluginPath)) {
            $this->error("Plugin directory already exists: {$pluginPath}");

            return self::FAILURE;
        }

        // Generate namespace
        $namespace = $this->option('namespace');
        if (! $namespace) {
            $namespace = $this->studlyCase($name);
        }

        // Generate class name
        $className = $this->studlyCase($name) . 'Plugin';

        $author = $this->option('author') ?? 'Unknown';
        $description = $this->option('description') ?? 'A custom MKS CMS plugin';

        $this->info("📦 Creating plugin: {$name}");
        $this->newLine();

        // Create directory structure
        $this->createDirectories($pluginPath);

        // Create files
        $this->createPluginPhp($pluginPath, $name, $namespace, $className, $author, $description);
        $this->createPluginClass($pluginPath, $namespace, $className, $name);
        $this->createComposerJson($pluginPath, $name, $namespace, $author, $description);
        $this->createRoutesFiles($pluginPath);

        // Auto-discover the new plugin
        $this->newLine();
        $this->line('🔍 Discovering new plugin...');
        $this->discoverNewPlugin();

        $this->newLine();
        $this->info('✅ Plugin created successfully!');
        $this->newLine();
        $this->line("Plugin location: <comment>{$pluginPath}</comment>");
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Go to <comment>Admin Panel → System → Plugins</comment>');
        $this->line("  2. Find your plugin <comment>\"{$name}\"</comment> and click <comment>\"Install\"</comment>");
        $this->line('  3. Click <comment>"Activate"</comment> to enable the plugin');
        $this->newLine();
        $this->line('Or use CLI:');
        $this->line("  <comment>php artisan mks-plugin:install {$name} && php artisan mks-plugin:activate {$name}</comment>");
        $this->newLine();
        $this->line('<fg=gray>Note: Autoloading is handled automatically - no need to modify composer.json!</>');

        return self::SUCCESS;
    }

    private function createDirectories(string $basePath): void
    {
        $directories = [
            $basePath,
            "{$basePath}/src",
            "{$basePath}/src/Models",
            "{$basePath}/src/Hooks",
            "{$basePath}/src/Hooks/Listeners",
            "{$basePath}/src/Filament",
            "{$basePath}/src/Filament/Resources",
            "{$basePath}/src/Filament/Pages",
            "{$basePath}/src/Filament/Widgets",
            "{$basePath}/database",
            "{$basePath}/database/migrations",
            "{$basePath}/config",
            "{$basePath}/resources",
            "{$basePath}/resources/views",
            "{$basePath}/resources/lang",
            "{$basePath}/routes",
        ];

        foreach ($directories as $dir) {
            $this->files->makeDirectory($dir, 0755, true, true);
            $this->line("  Created: {$dir}");
        }
    }

    private function createPluginPhp(
        string $basePath,
        string $name,
        string $namespace,
        string $className,
        string $author,
        string $description
    ): void {
        $content = <<<PHP
<?php

/**
 * Plugin Manifest for {$name}
 * 
 * This file defines the plugin metadata and configuration.
 * It is the source of truth for plugin information.
 */

return [
    // Unique plugin identifier (must match folder name)
    'id' => '{$name}',
    
    // Human-readable name
    'name' => '{$this->titleCase($name)}',
    
    // Plugin version (SemVer)
    'version' => '1.0.0',
    
    // Plugin description
    'description' => '{$description}',
    
    // Plugin author
    'author' => '{$author}',
    
    // Dependencies (other plugins or mksine version)
    'requires' => [
        'mksine' => '^1.0',
    ],
    
    // PHP Namespace
    'namespace' => '{$namespace}',
    
    // Main plugin class
    'plugin_class' => '{$namespace}\\{$className}',
    
    // PSR-4 autoload mapping
    'autoload' => [
        '{$namespace}\\\\' => 'src/',
    ],
    
    // Hooks exposed by this plugin
    'hooks' => [
        // Public hooks (other plugins can listen)
        'public' => [
            // '{$name}.example.created',
        ],
        
        // Private hooks (only this plugin can listen)
        'private' => [
            // '{$name}.internal.process',
        ],
    ],
    
    // Public API (optional)
    // 'public_api' => [
    //     'facade' => '{$namespace}\\Facades\\{$this->studlyCase($name)}',
    // ],
];
PHP;

        $path = "{$basePath}/plugin.php";
        $this->files->put($path, $content);
        $this->line("  Created: {$path}");
    }

    private function createPluginClass(
        string $basePath,
        string $namespace,
        string $className,
        string $name
    ): void {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;

class {$className} implements PluginInterface
{
    /**
     * Get the unique plugin identifier.
     */
    public function id(): string
    {
        return '{$name}';
    }

    /**
     * Called when plugin is first installed.
     */
    public function install(): void
    {
        // Run migrations, create initial data, etc.
    }

    /**
     * Called when plugin is activated.
     */
    public function activate(): void
    {
        // Register hooks, enable features
    }

    /**
     * Called when plugin is deactivated.
     */
    public function deactivate(): void
    {
        // Unregister hooks, disable features
        // Note: Data should NOT be deleted here
    }

    /**
     * Called when plugin is uninstalled.
     */
    public function uninstall(bool \$deleteData = false): void
    {
        if (\$deleteData) {
            // Delete tables, files, etc.
        }
    }

    /**
     * Called on every request when plugin is active.
     */
    public function boot(): void
    {
        // Register routes, bind services, etc.
    }

    /**
     * Get the plugin's migrations path.
     */
    public function migrationsPath(): ?string
    {
        return __DIR__ . '/../database/migrations';
    }

    /**
     * Get the plugin's config path.
     */
    public function configPath(): ?string
    {
        return __DIR__ . '/../config';
    }

    /**
     * Get the plugin's views path.
     */
    public function viewsPath(): ?string
    {
        return __DIR__ . '/../resources/views';
    }

    /**
     * Get the plugin's web routes path.
     */
    public function webRoutesPath(): ?string
    {
        return __DIR__ . '/../routes/web.php';
    }

    /**
     * Get the plugin's API routes path.
     */
    public function apiRoutesPath(): ?string
    {
        return __DIR__ . '/../routes/api.php';
    }

    /**
     * Get the plugin's translations path.
     */
    public function translationsPath(): ?string
    {
        return __DIR__ . '/../resources/lang';
    }

    /**
     * Get the plugin's Filament resources path.
     */
    public function filamentResourcesPath(): ?string
    {
        \$path = __DIR__ . '/Filament/Resources';
        return is_dir(\$path) ? \$path : null;
    }

    /**
     * Get the plugin's Filament pages path.
     */
    public function filamentPagesPath(): ?string
    {
        \$path = __DIR__ . '/Filament/Pages';
        return is_dir(\$path) ? \$path : null;
    }

    /**
     * Get the plugin's Filament widgets path.
     */
    public function filamentWidgetsPath(): ?string
    {
        \$path = __DIR__ . '/Filament/Widgets';
        return is_dir(\$path) ? \$path : null;
    }

    /**
     * Get the plugin's namespace.
     */
    public function namespace(): ?string
    {
        return '{$namespace}';
    }
}
PHP;

        $path = "{$basePath}/src/{$className}.php";
        $this->files->put($path, $content);
        $this->line("  Created: {$path}");
    }

    private function createComposerJson(
        string $basePath,
        string $name,
        string $namespace,
        string $author,
        string $description
    ): void {
        $escapedNamespace = str_replace('\\', '\\\\', $namespace);

        $content = <<<JSON
{
    "name": "mks-plugins/{$name}",
    "description": "{$description}",
    "type": "mks-plugin",
    "license": "MIT",
    "authors": [
        {
            "name": "{$author}"
        }
    ],
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "{$escapedNamespace}\\\\": "src/"
        }
    },
    "extra": {
        "mks-plugin": {
            "class": "{$escapedNamespace}\\\\{$this->studlyCase($name)}Plugin"
        }
    }
}
JSON;

        $path = "{$basePath}/composer.json";
        $this->files->put($path, $content);
        $this->line("  Created: {$path}");
    }

    private function createRoutesFiles(string $basePath): void
    {
        $webRoutesContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/**
 * Plugin Web Routes
 * 
 * These routes are automatically loaded by the MKS CMS plugin system.
 * They are wrapped in the 'web' middleware group.
 */

Route::get('/{$this->argument('name')}-example', function () {
    return 'Hello from {$this->argument('name')} plugin!';
});
PHP;

        $apiRoutesContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/**
 * Plugin API Routes
 * 
 * These routes are automatically loaded by the MKS CMS plugin system.
 * They are wrapped in the 'api' middleware group and prefixed with 'api/'.
 */

Route::get('/{$this->argument('name')}-test', function () {
    return ['status' => 'success', 'message' => 'API is working'];
});
PHP;

        $this->files->put("{$basePath}/routes/web.php", $webRoutesContent);
        $this->files->put("{$basePath}/routes/api.php", $apiRoutesContent);
        $this->line("  Created: {$basePath}/routes/web.php");
        $this->line("  Created: {$basePath}/routes/api.php");
    }

    private function studlyCase(string $value): string
    {
        $words = explode('-', $value);

        return implode('', array_map('ucfirst', $words));
    }

    private function titleCase(string $value): string
    {
        $words = explode('-', $value);

        return implode(' ', array_map('ucfirst', $words));
    }

    /**
     * Discover the newly created plugin.
     */
    private function discoverNewPlugin(): void
    {
        try {
            $discovery = new PluginDiscovery;
            $manifests = $discovery->rediscover();

            $this->line('  Found <info>' . count($manifests) . '</info> plugin(s)');
        } catch (\Exception $e) {
            $this->warn('  Could not auto-discover: ' . $e->getMessage());
        }
    }
}
