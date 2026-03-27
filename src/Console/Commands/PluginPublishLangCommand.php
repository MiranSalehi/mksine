<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginPublishLangCommand extends Command
{
    protected $signature = 'mks-plugin:publish-lang
                            {plugin? : Plugin ID to publish translations (publishes all if omitted)}';

    protected $description = 'Publish plugin translations to project lang directory (always overwrites)';

    public function handle(PluginManager $pluginManager): int
    {
        if (! function_exists('lang_path')) {
            $this->error('lang_path() is not available.');

            return self::FAILURE;
        }

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

        if ($manifest->publishTranslations()) {
            $this->info("Published translations for plugin '{$manifest->name()}'");
            $this->line('  → ' . lang_path('vendor/' . $pluginId));

            return self::SUCCESS;
        }

        $this->warn("No lang directory found for plugin '{$manifest->name()}'.");

        return self::SUCCESS;
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
            if ($manifest->publishTranslations()) {
                $this->line("  <info>✓</info> {$manifest->name()}");
                $published++;
            } else {
                $this->line("  <comment>⏭</comment> {$manifest->name()} (no lang found)");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Published: {$published}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
