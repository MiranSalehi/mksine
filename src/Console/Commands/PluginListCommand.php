<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;

class PluginListCommand extends Command
{
    protected $signature = 'mks-plugin:list {--status= : Filter by status (active, inactive, installed, not_installed)}';

    protected $description = 'List all discovered MKS CMS plugins';

    public function handle(PluginManager $pluginManager): int
    {
        $this->info('🔍 Discovering plugins...');
        $this->newLine();

        $plugins = $pluginManager->getAllPlugins();

        if (empty($plugins)) {
            $this->warn('No plugins found.');
            $this->line('Place plugins in: ' . base_path('plugins/'));

            return self::SUCCESS;
        }

        // Filter by status if provided
        $statusFilter = $this->option('status');
        if ($statusFilter) {
            $plugins = array_filter($plugins, fn ($p) => $p['status'] === $statusFilter);
        }

        // Build table
        $headers = ['ID', 'Name', 'Version', 'Status', 'Author'];
        $rows = [];

        foreach ($plugins as $plugin) {
            $status = $this->formatStatus($plugin['status']);

            $rows[] = [
                $plugin['id'],
                $plugin['name'],
                $plugin['version'],
                $status,
                $plugin['author'] ?? '-',
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->line('Total: ' . count($rows) . ' plugin(s)');

        return self::SUCCESS;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'active' => '<fg=green>● Active</>',
            'inactive' => '<fg=yellow>○ Inactive</>',
            'installed' => '<fg=blue>◐ Installed</>',
            'not_installed' => '<fg=gray>○ Not Installed</>',
            'boot_failed' => '<fg=red>✗ Boot Failed</>',
            default => $status,
        };
    }
}
