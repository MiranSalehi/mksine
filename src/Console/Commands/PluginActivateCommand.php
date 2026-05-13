<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginActivateCommand extends Command
{
    protected $signature = 'mks-plugin:activate {plugin : The plugin ID to activate}';

    protected $description = 'Activate an MKS CMS plugin';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');

        $this->info("🚀 Activating plugin: {$pluginId}");
        $this->newLine();

        try {
            $manifest = $pluginManager->getManifest($pluginId);

            if (! $manifest) {
                $this->error("Plugin not found: {$pluginId}");

                return self::FAILURE;
            }

            $status = $pluginManager->getStatus($pluginId);

            if ($status === 'active') {
                $this->warn('Plugin is already active.');

                return self::SUCCESS;
            }

            if ($status === 'boot_failed') {
                $this->warn('⚠️  This plugin previously failed during boot.');

                // Under --no-interaction (e.g. CI / tests), confirm() returns the default (false)
                // and would skip activation entirely — leaving the plugin stuck in boot_failed.
                $shouldRetry = $this->input->hasParameterOption(['--no-interaction', '-n'])
                    || $this->confirm('Do you want to try activating it again?', false);

                if (! $shouldRetry) {
                    return self::SUCCESS;
                }
            }

            // Activate (will auto-install if needed)
            $pluginManager->activate($pluginId);

            // Publish assets to public/plugins/{id}/ (mirrors theme system).
            $manifest = $pluginManager->getManifest($pluginId);
            if ($manifest && $manifest->publishAssets()) {
                $this->line("  → Assets published to: " . $manifest->publicPath());
            }

            $this->newLine();
            $this->info("✅ Plugin activated successfully: {$pluginId}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Activation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
