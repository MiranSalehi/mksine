<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\Migrator;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationCatalog;
use Miran\Mksine\SmartMigration\Capture\MigrationCaptureRunner;
use Miran\Mksine\SmartMigration\Diff\SchemaDiffPlanner;
use Miran\Mksine\SmartMigration\Execution\PendingMigrationRunner;
use Miran\Mksine\SmartMigration\Execution\SchemaSyncExecutor;
use Miran\Mksine\SmartMigration\Inspection\DatabaseSchemaInspector;
use Miran\Mksine\SmartMigration\Progress\SmartMigrationProgressStore;

final class SmartMigrationOrchestrator
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly ConnectionResolverInterface $resolver,
        private readonly MigrationCaptureRunner $captureRunner,
        private readonly SmartMigrationProgressStore $progressStore,
        private readonly SmartMigrationCatalog $catalog,
        private readonly PendingMigrationRunner $pendingRunner,
    ) {}

    /**
     * @param  list<string>  $migrationNames
     * @return array{
     *     executed_names: list<string>,
     *     pending_names: list<string>,
     *     unknown_names: list<string>,
     *     actions: list<\Miran\Mksine\SmartMigration\Diff\PlannedAction>,
     *     pending_runs: list<string>,
     *     notices: list<string>,
     *     warnings: list<string>
     * }
     */
    public function analyzeSelection(?string $database, array $migrationNames): array
    {
        $partition = $this->catalog->partitionNames($migrationNames);
        $plan = $this->analyze($database, null, $partition['executed']);
        $warnings = $plan['warnings'];

        if ($partition['unknown'] !== []) {
            $warnings[] = '[WARNING] Unknown migration(s): '.implode(', ', $partition['unknown']);
        }

        return [
            'executed_names' => $partition['executed'],
            'pending_names' => $partition['pending'],
            'unknown_names' => $partition['unknown'],
            'actions' => $plan['actions'],
            'pending_runs' => $this->pendingRunner->describe($partition['pending']),
            'notices' => $plan['notices'],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $migrationNames
     * @return list<string>
     */
    public function executeSelection(
        bool $dryRun,
        ?string $database,
        array $migrationNames,
        ?\Symfony\Component\Console\Output\OutputInterface $output = null,
    ): array {
        $partition = $this->catalog->partitionNames($migrationNames);
        $log = [];

        if ($partition['unknown'] !== []) {
            $log[] = '[WARNING] Unknown migration(s): '.implode(', ', $partition['unknown']);
        }

        $pendingLines = $this->pendingRunner->run($dryRun, $database, $partition['pending'], $output);
        $log = array_merge($log, $pendingLines);

        if ($partition['executed'] !== []) {
            $log = array_merge($log, $this->execute($dryRun, $database, null, $partition['executed']));
        }

        return $log;
    }

    /**
     * @return array{
     *     actions: list<\Miran\Mksine\SmartMigration\Diff\PlannedAction>,
     *     notices: list<string>,
     *     warnings: list<string>
     * }
     */
    public function analyze(?string $database = null, ?array $paths = null, ?array $migrationNames = null): array
    {
        $paths ??= $this->catalog->migrationPaths();
        $connection = $this->resolver->connection($database);
        $schema = $connection->getSchemaBuilder();

        $capture = $this->captureRunner->buildExpectedState($paths, $database, $migrationNames);
        $inspector = new DatabaseSchemaInspector($schema, $connection);
        $planner = new SchemaDiffPlanner($inspector);

        return $planner->plan($capture['state'], $capture['warnings']);
    }

    /**
     * @return list<string>
     */
    public function execute(bool $dryRun, ?string $database = null, ?array $paths = null, ?array $migrationNames = null): array
    {
        $plan = $this->analyze($database, $paths, $migrationNames);
        $connection = $this->resolver->connection($database);
        $schema = $connection->getSchemaBuilder();
        $inspector = new DatabaseSchemaInspector($schema, $connection);
        $executor = new SchemaSyncExecutor($schema, $inspector);

        $completed = $this->progressStore->completedActionIds();
        $log = [];

        foreach ($plan['notices'] as $notice) {
            $log[] = $notice;
        }

        foreach ($plan['warnings'] as $warning) {
            $log[] = $warning;
        }

        foreach ($plan['actions'] as $action) {
            if (in_array($action->id, $completed, true)) {
                continue;
            }

            if ($dryRun) {
                $log[] = 'Would add '.$action->label();
                $sql = $executor->describeSql($action);
                if ($sql !== null) {
                    $log[] = $sql;
                }

                continue;
            }

            try {
                $executor->execute($action);
                $this->progressStore->markCompleted($action->id);
                $log[] = '[OK] Added '.$action->label();
            } catch (\Throwable $exception) {
                $log[] = '[ERROR] Failed '.$action->label().': '.$exception->getMessage();

                throw $exception;
            }
        }

        if (! $dryRun) {
            $this->progressStore->clear();
        }

        if ($dryRun) {
            if ($plan['actions'] !== []) {
                $log[] = 'No changes executed.';
            }

            return $log;
        }

        return $log;
    }
}
