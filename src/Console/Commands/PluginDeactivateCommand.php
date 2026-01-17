<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginDeactivateCommand extends Command
{
    protected $signature = 'mks-plugin:deactivate {plugin : The plugin ID to deactivate}';

    protected $description = 'Deactivate an MKS CMS plugin';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');

        $this->info("⏸️  Deactivating plugin: {$pluginId}");
        $this->newLine();

        try {
            $manifest = $pluginManager->getManifest($pluginId);

            if (! $manifest) {
                $this->error("Plugin not found: {$pluginId}");

                return self::FAILURE;
            }

            $status = $pluginManager->getStatus($pluginId);

            if ($status !== 'active') {
                $this->warn("Plugin is not active (status: {$status})");

                return self::SUCCESS;
            }

            // Deactivate
            $pluginManager->deactivate($pluginId);

            $this->newLine();
            $this->info("✅ Plugin deactivated successfully: {$pluginId}");
            $this->line('Plugin data has been preserved.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Deactivation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
