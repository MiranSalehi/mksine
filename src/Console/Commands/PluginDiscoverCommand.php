<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginDiscoverCommand extends Command
{
    protected $signature = 'mks-plugin:discover {--clear : Clear cache before discovery}';

    protected $description = 'Discover and cache all MKS CMS plugins';

    public function handle(PluginManager $pluginManager): int
    {
        $this->info('🔍 Discovering plugins...');
        $this->newLine();

        $clearCache = $this->option('clear');

        if ($clearCache) {
            $this->line('Clearing plugin cache...');
            $pluginManager->clearCache();
        }

        $manifests = $pluginManager->discover($clearCache);

        if (empty($manifests)) {
            $this->warn('No plugins discovered.');
            $this->line('Place plugins in: ' . base_path('plugins/'));

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
