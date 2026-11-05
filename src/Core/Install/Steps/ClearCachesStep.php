<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;
use Throwable;

class ClearCachesStep extends AbstractInstallStep
{
    /** @var list<string> */
    public const COMMANDS = ['optimize:clear', 'filament:optimize-clear'];

    /**
     * optimize:clear also empties the cache store, which fails while a database cache
     * table does not exist yet (before migrations). These clear everything else.
     *
     * @var list<string>
     */
    public const COMMANDS_WITHOUT_CACHE_STORE = ['config:clear', 'route:clear', 'view:clear', 'event:clear', 'clear-compiled', 'filament:optimize-clear'];

    public function key(): string
    {
        return 'clear-caches';
    }

    public function run(InstallContext $context): StepResult
    {
        $warnings = [];

        foreach ($this->commands() as $command) {
            try {
                [$code] = $this->artisan($command);

                if ($code !== 0) {
                    $warnings[] = $this->message('command_failed', ['command' => $command]);
                }
            } catch (Throwable $exception) {
                $warnings[] = $this->message('command_failed', ['command' => $command]) . ' ' . $exception->getMessage();
            }
        }

        return StepResult::completed([$this->message('caches_cleared')], $warnings);
    }

    /**
     * @return list<string>
     */
    private function commands(): array
    {
        $commands = $this->cacheStoreIsReady() ? self::COMMANDS : self::COMMANDS_WITHOUT_CACHE_STORE;

        // filament:optimize-clear calls icons:clear, which is only registered on the console.
        if (! app()->runningInConsole()) {
            $commands = array_values(array_diff($commands, ['filament:optimize-clear']));
        }

        return $commands;
    }

    private function cacheStoreIsReady(): bool
    {
        $store = (string) config('cache.default');

        if (config("cache.stores.{$store}.driver") !== 'database') {
            return true;
        }

        return $this->hasTable((string) config("cache.stores.{$store}.table", 'cache'));
    }
}
