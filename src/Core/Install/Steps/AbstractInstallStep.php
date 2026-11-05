<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Install\Contracts\InstallStep;
use Throwable;

abstract class AbstractInstallStep implements InstallStep
{
    public function label(): string
    {
        return __('mksine::install.steps.' . $this->key());
    }

    /**
     * Run an artisan command in-process (no exec/proc_open, so it works on shared hosting).
     *
     * @param  array<string, mixed>  $parameters
     * @return array{0: int, 1: string} Exit code and trimmed output.
     */
    protected function artisan(string $command, array $parameters = []): array
    {
        $code = Artisan::call($command, $parameters);

        return [$code, trim(Artisan::output())];
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    protected function databaseIsReady(): bool
    {
        return $this->hasTable('permissions') || $this->hasTable('mks_hooks');
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    protected function message(string $key, array $replace = []): string
    {
        return __('mksine::install.messages.' . $key, $replace);
    }
}
