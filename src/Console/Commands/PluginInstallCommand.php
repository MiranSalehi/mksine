<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginInstallCommand extends Command
{
    protected $signature = 'mks-plugin:install {plugin : The plugin ID to install}';

    protected $description = 'Install an MKS CMS plugin';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');

        $this->info("📦 Installing plugin: {$pluginId}");
        $this->newLine();

        try {
            $manifest = $pluginManager->getManifest($pluginId);

            if (! $manifest) {
                $this->error("Plugin not found: {$pluginId}");
                $this->line('Run `php artisan mks:plugin:list` to see available plugins.');

                return self::FAILURE;
            }

            $status = $pluginManager->getStatus($pluginId);

            if ($status !== 'not_installed') {
                $this->warn("Plugin is already installed (status: {$status})");

                return self::SUCCESS;
            }

            // Show plugin info
            $this->table(['Property', 'Value'], [
                ['Name', $manifest->name()],
                ['Version', $manifest->version()],
                ['Author', $manifest->author() ?? '-'],
                ['Description', $manifest->description() ?? '-'],
            ]);

            if (! $this->confirm('Do you want to install this plugin?', true)) {
                $this->info('Installation cancelled.');

                return self::SUCCESS;
            }

            // Install
            $this->line('Running installation...');
            $pluginManager->install($pluginId);

            $this->newLine();
            $this->info("✅ Plugin installed successfully: {$pluginId}");
            $this->line('Run `php artisan mks:plugin:activate ' . $pluginId . '` to activate it.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Installation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
