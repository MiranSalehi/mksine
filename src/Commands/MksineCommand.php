<?php

namespace Miran\Mksine\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Mksine;

class MksineCommand extends Command
{
    public $signature = 'mksine:info';

    public $description = 'Display MKSine information';

    public function handle(Mksine $cms): int
    {
        $this->info('MKSine Information');
        $this->newLine();
        $this->line('Version: ' . $cms->version());
        $this->line('Features:');

        $features = $cms->config('features', []);
        foreach ($features as $feature => $enabled) {
            $status = $enabled ? '<fg=green>✓ Enabled</>' : '<fg=red>✗ Disabled</>';
            $this->line("  - {$feature}: {$status}");
        }

        return self::SUCCESS;
    }
}
