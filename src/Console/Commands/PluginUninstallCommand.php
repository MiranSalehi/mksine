<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginUninstallCommand extends Command
{
    protected $signature = 'mks-plugin:uninstall 
                            {plugin : The plugin ID to uninstall}
                            {--delete-data : Also delete all plugin data (tables, files, etc.)}';

    protected $description = 'Uninstall an MKS CMS plugin';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');
        $deleteData = $this->option('delete-data');

        $this->info("🗑️  Uninstalling plugin: {$pluginId}");
        $this->newLine();

        try {
            $manifest = $pluginManager->getManifest($pluginId);

            if (! $manifest) {
                $this->error("Plugin not found: {$pluginId}");

                return self::FAILURE;
            }

            $status = $pluginManager->getStatus($pluginId);

            if ($status === 'not_installed') {
                $this->warn('Plugin is not installed.');

                return self::SUCCESS;
            }

            // Confirm uninstall
            $this->warn("⚠️  This will uninstall the plugin: {$manifest->name()}");

            if ($deleteData) {
                $this->error('⚠️  WARNING: --delete-data flag is set. This will DELETE ALL PLUGIN DATA!');
            } else {
                $this->line('Plugin data will be preserved. Use --delete-data to remove all data.');
            }

            if (! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Uninstallation cancelled.');

                return self::SUCCESS;
            }

            if ($deleteData) {
                if (! $this->confirm('FINAL CONFIRMATION: Delete all plugin data permanently?', false)) {
                    $this->info('Uninstallation cancelled.');

                    return self::SUCCESS;
                }
            }

            // Uninstall
            $pluginManager->uninstall($pluginId, $deleteData);

            $this->newLine();
            $this->info("✅ Plugin uninstalled successfully: {$pluginId}");

            if (! $deleteData) {
                $this->line('Plugin data has been preserved in the database.');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Uninstallation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
