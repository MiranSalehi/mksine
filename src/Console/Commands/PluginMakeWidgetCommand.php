<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginMakeWidgetCommand extends Command
{
    protected $signature = 'mks-plugin:make-widget
                            {plugin : The plugin ID (e.g., my-shop)}
                            {name : The widget name (e.g., StatsOverview)}
                            {--chart : Create a chart widget}
                            {--stats : Create a stats overview widget}';

    protected $description = 'Create a new Filament widget for a plugin';

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
        $isChart = $this->option('chart');
        $isStats = $this->option('stats');

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

        // Determine widget type
        $type = 'basic';
        if ($isChart) {
            $type = 'chart';
        } elseif ($isStats) {
            $type = 'stats';
        }

        // Create widget
        $this->createWidget($pluginPath, $namespace, $name, $type);

        // Create view for basic widgets
        if ($type === 'basic') {
            $this->createView($pluginPath, $name);
        }

        $this->newLine();
        $this->info("✅ Widget [{$name}] created successfully!");
        $this->newLine();
        $this->line('Files created:');
        $this->line("  <comment>src/Filament/Widgets/{$name}.php</comment>");

        if ($type === 'basic') {
            $this->line('  <comment>resources/views/filament/widgets/' . Str::kebab($name) . '.blade.php</comment>');
        }

        return self::SUCCESS;
    }

    private function createWidget(string $pluginPath, string $namespace, string $name, string $type): void
    {
        $widgetsPath = $pluginPath . '/src/Filament/Widgets';
        $this->files->ensureDirectoryExists($widgetsPath);

        $stub = match ($type) {
            'chart' => $this->getChartWidgetStub($namespace, $name),
            'stats' => $this->getStatsWidgetStub($namespace, $name),
            default => $this->getBasicWidgetStub($namespace, $name),
        };

        $this->files->put("{$widgetsPath}/{$name}.php", $stub);
    }

    private function createView(string $pluginPath, string $name): void
    {
        $viewsPath = $pluginPath . '/resources/views/filament/widgets';
        $this->files->ensureDirectoryExists($viewsPath);

        $viewName = Str::kebab($name);
        $stub = $this->getViewStub($name);
        $this->files->put("{$viewsPath}/{$viewName}.blade.php", $stub);
    }

    private function getBasicWidgetStub(string $namespace, string $name): string
    {
        $viewName = Str::kebab($name);
        $pluginViewPrefix = Str::kebab(class_basename($namespace));

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Widgets;

use Filament\\Widgets\\Widget;

class {$name} extends Widget
{
    protected static string \$view = '{$pluginViewPrefix}::filament.widgets.{$viewName}';

    protected int|string|array \$columnSpan = 'full';
}
PHP;
    }

    private function getChartWidgetStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Widgets;

use Filament\\Widgets\\ChartWidget;

class {$name} extends ChartWidget
{
    protected int|string|array \$columnSpan = 'full';

    public function getHeading(): string
    {
        return __('{$name}');
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Data',
                    'data' => [0, 10, 5, 2, 21, 32, 45, 74, 65, 45, 77, 89],
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
PHP;
    }

    private function getStatsWidgetStub(string $namespace, string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Filament\\Widgets;

use Filament\\Widgets\\StatsOverviewWidget as BaseWidget;
use Filament\\Widgets\\StatsOverviewWidget\\Stat;

class {$name} extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', '192.1k')
                ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Bounce Rate', '21%')
                ->description('7% decrease')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Average Time', '3:12')
                ->description('3% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
PHP;
    }

    private function getViewStub(string $name): string
    {
        return <<<BLADE
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <div class="flex-1">
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('{$name}') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Your widget content goes here.') }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
BLADE;
    }
}
