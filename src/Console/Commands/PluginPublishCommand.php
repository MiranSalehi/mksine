<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginPublishCommand extends Command
{
    protected $signature = 'mks-plugin:publish
                            {plugin? : Plugin ID to publish (publishes all if omitted)}
                            {--force : Overwrite existing published assets}';

    protected $description = 'Publish plugin assets (resources/dist/) to the public directory';

    public function handle(PluginManager $pluginManager): int
    {
        $pluginId = $this->argument('plugin');

        if ($pluginId) {
            return $this->publishOne($pluginManager, $pluginId);
        }

        return $this->publishAll($pluginManager);
    }

    private function publishOne(PluginManager $pluginManager, string $pluginId): int
    {
        $pluginManager->initialize();

        $manifest = $pluginManager->getManifest($pluginId);

        if (! $manifest) {
            $this->error("Plugin not found: {$pluginId}");

            return self::FAILURE;
        }

        if ($manifest->publishAssets()) {
            $this->info("Published assets for plugin '{$manifest->name()}'");
            $this->line('  → ' . $manifest->publicPath());

            return self::SUCCESS;
        }

        $this->warn("No dist/ directory found for plugin '{$manifest->name()}'.");
        $this->line('  Run: cd plugins/' . $pluginId . ' && npm run build');

        return self::FAILURE;
    }

    private function publishAll(PluginManager $pluginManager): int
    {
        $pluginManager->initialize();

        $manifests = $pluginManager->getRegistry()->getManifests();

        if (empty($manifests)) {
            $this->warn('No plugins found.');

            return self::SUCCESS;
        }

        $published = 0;
        $skipped = 0;

        foreach ($manifests as $manifest) {
            if ($manifest->publishAssets()) {
                $this->line("  <info>✓</info> {$manifest->name()}");
                $published++;
            } else {
                $this->line("  <comment>⏭</comment> {$manifest->name()} (no dist/ found)");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Published: {$published}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
