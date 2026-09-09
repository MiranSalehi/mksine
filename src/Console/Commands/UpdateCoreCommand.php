<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Support\ComposerBinary;
use Miran\Mksine\Support\Console\AdminConsolePhpBinary;
use Miran\Mksine\Support\PackageVersion;
use Symfony\Component\Process\Process;

/**
 * Updates miran/mksine via Composer. Does not accept a ZIP — replacing
 * vendor/ or packages/mksine from a ZIP would desync composer.lock.
 *
 *   php artisan mksine:update --force
 */
class UpdateCoreCommand extends Command
{
    protected $signature = 'mksine:update
        {--force : Skip confirmation (required for non-interactive runs)}
        {--full-migrate : Run all pending application migrations instead of package migrations only}';

    protected $description = 'Update miran/mksine with Composer, then publish package migrations and migrate.';

    public function handle(): int
    {
        if (! config('mksine.updater.enabled', true)) {
            $this->error('Updater is disabled via config(mksine.updater.enabled).');

            return self::FAILURE;
        }

        $projectRoot = base_path();
        $current = PackageVersion::current();
        $this->info('Installed miran/mksine version: '.$current);

        $composerArgv = ComposerBinary::argv($projectRoot);
        if ($composerArgv === null) {
            $this->error('Composer is not available on this server.');
            $this->newLine();
            $this->line($this->offlinePlaybook());

            return self::FAILURE;
        }

        $this->line('This will run: composer update miran/mksine');
        $this->warn('ZIP replacement of vendor/ or packages/mksine is not supported — it would break composer.lock and the autoloader.');

        if ($this->input->isInteractive()) {
            if (! $this->confirm('Continue?', false)) {
                $this->line('Aborted.');

                return self::INVALID;
            }
        } elseif (! $this->option('force')) {
            $this->error('Non-interactive runs require --force.');

            return self::INVALID;
        }

        $update = new Process(
            array_merge($composerArgv, ['update', 'miran/mksine']),
            $projectRoot,
        );
        $update->setTimeout(600);
        $update->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $update->isSuccessful()) {
            $this->error('composer update miran/mksine failed (exit '.$update->getExitCode().').');

            return self::FAILURE;
        }

        $finishCode = $this->runPostComposerArtisan();
        if ($finishCode !== self::SUCCESS) {
            return $finishCode;
        }

        $this->info('miran/mksine is now '.$this->freshPackageVersion().' (was '.$current.').');
        $this->line('If this app uses a path repository, pull packages/mksine before composer update — Composer will not git-pull the path for you.');

        return self::SUCCESS;
    }

    private function runPostComposerArtisan(): int
    {
        $php = $this->phpBinary();
        $artisan = base_path('artisan');

        $publish = new Process(
            [$php, $artisan, 'vendor:publish', '--tag=mksine-migrations', '--force'],
            base_path(),
        );
        $publish->setTimeout(120);
        $publish->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });
        if (! $publish->isSuccessful()) {
            $this->error('vendor:publish --tag=mksine-migrations failed.');

            return self::FAILURE;
        }

        $migrateArgv = [$php, $artisan, 'migrate', '--force'];
        if (! $this->option('full-migrate')) {
            $migrateArgv[] = '--path='.PackageVersion::migrationsPathRelativeToBase();
        }

        $migrate = new Process($migrateArgv, base_path());
        $migrate->setTimeout(300);
        $migrate->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });
        if (! $migrate->isSuccessful()) {
            $this->error('migrate failed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function phpBinary(): string
    {
        try {
            return AdminConsolePhpBinary::path();
        } catch (\InvalidArgumentException) {
            return PHP_BINARY;
        }
    }

    private function freshPackageVersion(): string
    {
        return PackageVersion::current();
    }

    private function offlinePlaybook(): string
    {
        return <<<'TEXT'
Update the package on a machine that has Composer, then deploy the lockfile and vendor tree:

  composer update miran/mksine
  php artisan vendor:publish --tag=mksine-migrations
  php artisan migrate --force

Copy at least composer.json, composer.lock, and vendor/ to the server.
If you use a path repository, also copy packages/mksine after pulling the new package sources.

On hosts without Composer, php artisan mks:release-archive can package that deployable tree.
Core ZIP uploads are not supported: they cannot update composer.lock or dump the autoloader.
TEXT;
    }
}
