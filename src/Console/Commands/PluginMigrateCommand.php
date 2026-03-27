<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginMigrateCommand extends Command
{
    protected $signature = 'mks-plugin:migrate {plugin? : Plugin ID; omit to migrate all installed plugins}';

    protected $description = 'Run pending database migrations for installed plugin(s)';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');

        try {
            if ($pluginId !== null && $pluginId !== '') {
                $this->info("Running migrations for plugin: {$pluginId}");
                $pluginManager->migratePluginDatabase($pluginId);
                $this->info('Done.');

                return self::SUCCESS;
            }

            $this->info('Running migrations for all installed plugins…');
            $pluginManager->migrateAllInstalledPlugins();
            $this->info('Done.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
