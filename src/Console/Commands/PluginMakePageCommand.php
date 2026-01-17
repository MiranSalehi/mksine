<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginMakePageCommand extends Command
{
    protected $signature = 'mks-plugin:make-page
                            {plugin : The plugin ID (e.g., my-shop)}
                            {name : The page name (e.g., Settings)}';

    protected $description = 'Create a new Filament page for a plugin';

    public function __construct(
        private readonly Filesystem $files,
        private readonly PluginManager $pluginManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $pluginId = $this->argument('plugin');
        $name = $this->argument('name');

        // Get plugin manifest
        $manifest = $this->pluginManager->getManifest($pluginId);

        if (! $manifest) {
            $this->error("Plugin '{$pluginId}' not found. Run 'php artisan mks-plugin:discover' first.");

            return self::FAILURE;
        }

        $pluginPath = $manifest->basePath();
        $namespace = $manifest->namespace();

        if (! $namespace) {
            $this->error("Plugin '{$pluginId}' does not have a namespace defined in plugin.php");

            return self::FAILURE;
        }

        // Create page
        $this->createPage($pluginPath, $namespace, $name);

        // Create view
        $this->createView($pluginPath, $name);

        $this->newLine();
        $this->info("✅ Page [{$name}] created successfully!");
        $this->newLine();
        $this->line('Files created:');
        $this->line("  <comment>src/Filament/Pages/{$name}.php</comment>");
        $this->line('  <comment>resources/views/filament/pages/' . Str::kebab($name) . '.blade.php</comment>');

        return self::SUCCESS;
    }

    private function createPage(string $pluginPath, string $namespace, string $name): void
    {
        $pagesPath = $pluginPath . '/src/Filament/Pages';
        $this->files->ensureDirectoryExists($pagesPath);

        $stub = $this->getPageStub($namespace, $name);
        $this->files->put("{$pagesPath}/{$name}.php", $stub);
    }

    private function createView(string $pluginPath, string $name): void
    {
        $viewsPath = $pluginPath . '/resources/views/filament/pages';
        $this->files->ensureDirectoryExists($viewsPath);

        $viewName = Str::kebab($name);
        $stub = $this->getViewStub($name);
        $this->files->put("{$viewsPath}/{$viewName}.blade.php", $stub);
    }

    private function getPageStub(string $namespace, string $name): string
    {
        $viewName = Str::kebab($name);
        $pluginViewPrefix = Str::kebab(class_basename($namespace));

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Pages;

use BackedEnum;
use Filament\\Pages\\Page;

class {$name} extends Page
{
    protected static string|BackedEnum|null \$navigationIcon = 'heroicon-o-document-text';

    protected string \$view = '{$pluginViewPrefix}::filament.pages.{$viewName}';

    public static function getNavigationLabel(): string
    {
        return __('{$name}');
    }

    public function getTitle(): string
    {
        return __('{$name}');
    }
}
PHP;
    }

    private function getViewStub(string $name): string
    {
        return <<<BLADE
<x-filament-panels::page>
    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ __('{$name}') }}
        </h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('This is the {$name} page. Add your content here.') }}
        </p>
    </div>
</x-filament-panels::page>
BLADE;
    }
}
