<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginDiscovery;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginDiscoverCommand extends Command
{
    protected $signature = 'mks-plugin:discover';

    protected $description = 'Discover and cache all MKS CMS plugins (always clears discovery cache first)';

    public function handle(PluginManager $pluginManager): int
    {
        $this->info('🔍 Discovering plugins...');
        $this->newLine();

        $this->line('Clearing plugin discovery cache…');
        $manifests = $pluginManager->discover(clearCache: true);

        if (empty($manifests)) {
            $this->warn('No plugins discovered.');
            $this->line('Place plugins in: '.PluginDiscovery::defaultPluginsPath().'/');

            return self::SUCCESS;
        }

        $this->info('✅ Discovered ' . count($manifests) . ' plugin(s):');
        $this->newLine();

        foreach ($manifests as $pluginId => $manifest) {
            $this->line("  • {$manifest->name()} ({$pluginId}) v{$manifest->version()}");
        }

        $this->newLine();
        $this->info('Plugin discovery cache updated.');

        return self::SUCCESS;
    }
}
