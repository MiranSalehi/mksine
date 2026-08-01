<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Services\DiscoveryService;

/**
 * Command to discover and sync listeners with the database.
 */
class DiscoverHooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mks:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and sync hook listeners with the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Discovering hook listeners...');

        $paths = $this->resolveDiscoveryPaths();
        if ($paths === []) {
            $this->error('No valid hook listener directories to scan.');

            return self::FAILURE;
        }

        $discovery = app(DiscoveryService::class);

        $mergedListeners = [];
        $mergedFormHooks = [];
        $mergedFormSlotHooks = [];
        $mergedTableHooks = [];

        foreach ($paths as $path) {
            $this->line("Scanning: {$path}");
            $batch = $discovery->discoverAll($path);

            foreach ($batch['listeners'] as $row) {
                $mergedListeners[$row['listener_class']] = $row;
            }

            foreach ($batch['form_hooks'] as $formName => $listenerClass) {
                $mergedFormHooks[$formName] = $listenerClass;
            }

            foreach ($batch['form_slot_hooks'] ?? [] as $listenerClass => $slotHook) {
                $mergedFormSlotHooks[$listenerClass] = $slotHook;
            }

            foreach ($batch['table_hooks'] as $tableName => $listenerClass) {
                $mergedTableHooks[$tableName] = $listenerClass;
            }
        }

        $discoveredListeners = array_values($mergedListeners);

        if ($discoveredListeners === [] && $mergedFormHooks === [] && $mergedFormSlotHooks === [] && $mergedTableHooks === []) {
            $this->warn('No listeners or hooks found.');

            return self::SUCCESS;
        }

        if (! $this->databaseReady()) {
            return self::FAILURE;
        }

        if ($discoveredListeners !== []) {
            $this->info('Found '.count($discoveredListeners).' event listener(s).');
            $synced = $discovery->syncListeners($discoveredListeners);
            $this->info("Synced {$synced} event listener(s) with database.");
        }

        if ($mergedFormHooks !== []) {
            $this->info('Found '.count($mergedFormHooks).' form hook(s).');
            $synced = $discovery->syncFormHooks($mergedFormHooks);
            $this->info("Synced {$synced} form hook(s) with database.");
        }

        if ($mergedFormSlotHooks !== []) {
            $this->info('Found '.count($mergedFormSlotHooks).' form slot hook(s).');
            $synced = $discovery->syncFormSlotHooks($mergedFormSlotHooks);
            $this->info("Synced {$synced} form slot hook(s) with database.");
        }

        if ($mergedTableHooks !== []) {
            $this->info('Found '.count($mergedTableHooks).' table hook(s).');
            $synced = $discovery->syncTableHooks($mergedTableHooks);
            $this->info("Synced {$synced} table hook(s) with database.");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveDiscoveryPaths(): array
    {
        $packageListeners = realpath(__DIR__.'/../../Core/Listeners');
        $paths = [];

        if ($packageListeners !== false) {
            $paths[] = $packageListeners;
        }

        $extra = config('mksine.hooks.discovery_paths', []);
        if (! is_array($extra)) {
            $extra = [];
        }

        foreach ($extra as $rawPath) {
            if (! is_string($rawPath) || $rawPath === '') {
                continue;
            }

            $resolved = $rawPath;
            if ($resolved[0] !== DIRECTORY_SEPARATOR && ! preg_match('#^[A-Za-z]:[\\\\/]#', $resolved)) {
                $resolved = base_path($resolved);
            }

            $real = realpath($resolved);
            if ($real === false || ! is_dir($real)) {
                $this->warn("Skipping missing or invalid discovery path: {$rawPath}");

                continue;
            }

            $paths[] = $real;
        }

        return array_values(array_unique($paths));
    }

    private function databaseReady(): bool
    {
        try {
            if (! Schema::hasTable('mks_hooks')) {
                $this->warn('mks_hooks table does not exist. Please run migrations first.');

                return false;
            }
        } catch (\Exception $e) {
            $this->warn('Database connection error: '.$e->getMessage());

            return false;
        }

        return true;
    }
}
