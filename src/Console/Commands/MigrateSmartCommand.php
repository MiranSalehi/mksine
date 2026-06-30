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
                            {--migration=* : Specific migration name(s) to sync or run}';

    protected $description = 'Run pending migrations and synchronize missing schema from executed migrations (additive only)';

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

        if ($migrationNames === []) {
            $this->components->info('Nothing to do.');

            return self::SUCCESS;
        }

        $selection = $orchestrator->analyzeSelection($database, $migrationNames);

        foreach ($selection['notices'] as $notice) {
            $this->components->warn($notice);
        }

        foreach ($selection['warnings'] as $warning) {
            $this->components->warn($warning);
        }

        if ($selection['actions'] === [] && $selection['pending_runs'] === []) {
            $this->components->info('Nothing to synchronize or run.');

            return self::SUCCESS;
        }

        $this->displaySummary($selection['pending_runs'], $selection['actions']);

        if ($dryRun) {
            $lines = $orchestrator->executeSelection(true, $database, $migrationNames, $this->output);

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

        $lines = $orchestrator->executeSelection(false, $database, $migrationNames, $this->output);

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
        $this->components->info('Smart migration finished.');

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

        $counts = $catalog->counts();

        if ($counts['total'] === 0) {
            $this->components->info('No migration files were found.');

            return [];
        }

        $this->components->info(sprintf(
            'Loaded %d migration file(s): %d executed (in migrations table), %d pending.',
            $counts['total'],
            $counts['executed'],
            $counts['pending'],
        ));

        $mode = select(
            label: 'How would you like to proceed?',
            options: [
                'all' => 'Smart-sync all executed migrations',
                'search' => 'Search and select migration(s)',
                'single' => 'Pick one migration',
                'cancel' => 'Cancel',
            ],
            hint: 'Pending migrations will be run normally. Executed migrations will be smart-synced.',
        );

        return match ($mode) {
            'all' => array_map(fn ($entry) => $entry->name, $catalog->executedEntries()),
            'search' => multisearch(
                label: 'Select migrations to run or synchronize',
                options: fn (string $value): array => $catalog->searchOptions($value, executedOnly: false),
                placeholder: 'Search by name or source…',
                hint: 'Pending = run migration. Ran = smart-sync schema. Space to toggle, enter to confirm.',
            ),
            'single' => [
                search(
                    label: 'Select a migration to run or synchronize',
                    options: fn (string $value): array => $catalog->searchOptions($value, executedOnly: false),
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
     * @param  list<string>  $pendingRuns
     * @param  list<\Miran\Mksine\SmartMigration\Diff\PlannedAction>  $actions
     */
    private function displaySummary(array $pendingRuns, array $actions): void
    {
        $this->components->info('The following changes will be applied:');

        foreach ($pendingRuns as $pendingRun) {
            $this->line('+ '.$pendingRun);
        }

        foreach ($actions as $action) {
            $this->line('+ '.$action->label());
        }

        $this->newLine();
    }
}
