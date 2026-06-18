<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationCatalog;
use Miran\Mksine\SmartMigration\SmartMigrationOrchestrator;

use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class MigrateSmartCommand extends Command
{
    protected $signature = 'migrate:smart
                            {--database= : The database connection to use}
                            {--dry-run : Preview changes without modifying the database}
                            {--force : Force the operation to run when in production}
                            {--all : Sync all executed migrations without prompting}
                            {--migration=* : Specific migration name(s) to sync}';

    protected $description = 'Synchronize missing schema elements from already-executed migrations (additive only)';

    public function handle(
        SmartMigrationOrchestrator $orchestrator,
        SmartMigrationCatalog $catalog,
    ): int {
        $database = $this->normalizedDatabaseOption();
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $migrationNames = $this->resolveMigrationSelection($catalog);

        if ($migrationNames === null) {
            $this->components->warn('Synchronization cancelled.');

            return self::SUCCESS;
        }

        $plan = $orchestrator->analyze($database, null, $migrationNames);

        foreach ($plan['notices'] as $notice) {
            $this->components->warn($notice);
        }

        foreach ($plan['warnings'] as $warning) {
            $this->components->warn($warning);
        }

        if ($plan['actions'] === []) {
            $this->components->info('Nothing to synchronize.');

            return self::SUCCESS;
        }

        $this->displaySummary($plan['actions']);

        if ($dryRun) {
            $lines = $orchestrator->execute(true, $database, null, $migrationNames);

            foreach ($lines as $line) {
                $this->line($line);
            }

            return self::SUCCESS;
        }

        if (App::environment('production') && ! $force) {
            $this->components->warn('Application In Production!');
        }

        if (App::environment('production') && ! $force && ! $this->confirm('Do you really wish to run this command?')) {
            $this->components->warn('Synchronization cancelled.');

            return self::SUCCESS;
        }

        $lines = $orchestrator->execute(false, $database, null, $migrationNames);

        foreach ($lines as $line) {
            if (str_starts_with($line, '[OK]')) {
                $this->components->info($line);
            } elseif (str_starts_with($line, '[WARNING]') || str_starts_with($line, '[NOTICE]')) {
                $this->components->warn($line);
            } else {
                $this->line($line);
            }
        }

        $this->newLine();
        $this->components->info('Smart migration synchronization finished.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function resolveMigrationSelection(SmartMigrationCatalog $catalog): ?array
    {
        /** @var list<string> $explicit */
        $explicit = array_values(array_filter((array) $this->option('migration'), fn (mixed $value): bool => is_string($value) && $value !== ''));

        if ($explicit !== []) {
            return $explicit;
        }

        if ($this->option('all') || ! $this->input->isInteractive()) {
            return array_map(
                fn ($entry) => $entry->name,
                $catalog->executedEntries(),
            );
        }

        $executed = $catalog->executedEntries();

        if ($executed === []) {
            $this->components->info('No executed migrations were found.');

            return [];
        }

        $this->components->info(sprintf(
            'Loaded %d executed migration(s) from app, plugins, and themes.',
            count($executed),
        ));

        $mode = select(
            label: 'How would you like to synchronize?',
            options: [
                'all' => 'Run all executed migrations',
                'search' => 'Search and select migration(s)',
                'single' => 'Pick one migration',
                'cancel' => 'Cancel',
            ],
            hint: 'Only migrations already recorded in the migrations table are listed.',
        );

        return match ($mode) {
            'all' => array_map(fn ($entry) => $entry->name, $executed),
            'search' => multisearch(
                label: 'Select migrations to synchronize',
                options: fn (string $value): array => $catalog->searchOptions($value),
                placeholder: 'Search by name or source…',
                hint: 'Space to toggle, enter to confirm.',
            ),
            'single' => [
                search(
                    label: 'Select a migration to synchronize',
                    options: fn (string $value): array => $catalog->searchOptions($value),
                    placeholder: 'Search by name or source…',
                ),
            ],
            default => null,
        };
    }

    private function normalizedDatabaseOption(): ?string
    {
        $database = $this->option('database');

        return is_string($database) && $database !== '' ? $database : null;
    }

    /**
     * @param  list<\Miran\Mksine\SmartMigration\Diff\PlannedAction>  $actions
     */
    private function displaySummary(array $actions): void
    {
        $this->components->info('The following changes will be applied:');

        foreach ($actions as $action) {
            $this->line('+ '.$action->label());
        }

        $this->newLine();
    }
}
