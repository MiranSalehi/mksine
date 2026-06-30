<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Interactive;

use Illuminate\Support\Facades\App;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationCatalog;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationEntry;
use Miran\Mksine\SmartMigration\Diff\PlannedAction;
use Miran\Mksine\SmartMigration\SmartMigrationOrchestrator;

final class MigrateSmartInteractiveHandler
{
    public function __construct(
        private readonly SmartMigrationCatalog $catalog,
        private readonly SmartMigrationOrchestrator $orchestrator,
    ) {}

    /**
     * @return array{
     *     count: int,
     *     executed_count: int,
     *     pending_count: int,
     *     migrations: list<array{
     *         name: string,
     *         source_label: string,
     *         source_key: string,
     *         label: string,
     *         executed: bool,
     *         status: string
     *     }>
     * }
     */
    public function catalog(): array
    {
        $counts = $this->catalog->counts();

        return [
            'count' => $counts['total'],
            'executed_count' => $counts['executed'],
            'pending_count' => $counts['pending'],
            'migrations' => array_map(
                fn (SmartMigrationEntry $entry): array => $this->serializeEntry($entry),
                $this->catalog->entries(),
            ),
        ];
    }

    /**
     * @param  list<string>  $migrationNames
     * @return array{
     *     actions: list<array{id: string, label: string}>,
     *     pending_runs: list<array{label: string}>,
     *     notices: list<string>,
     *     warnings: list<string>
     * }
     */
    public function analyze(array $migrationNames, ?string $database = null): array
    {
        $selection = $this->orchestrator->analyzeSelection($database, $migrationNames);

        return [
            'actions' => array_map(
                fn (PlannedAction $action): array => [
                    'id' => $action->id,
                    'label' => $action->label(),
                ],
                $selection['actions'],
            ),
            'pending_runs' => array_map(
                fn (string $label): array => ['label' => $label],
                $selection['pending_runs'],
            ),
            'notices' => $selection['notices'],
            'warnings' => $selection['warnings'],
        ];
    }

    /**
     * @param  list<string>  $migrationNames
     * @return array{lines: list<string>, exit_code: int, production: bool}
     */
    public function execute(array $migrationNames, bool $dryRun, bool $force, ?string $database = null): array
    {
        if (App::environment('production') && ! $force && ! $dryRun) {
            return [
                'lines' => ['Application In Production!'],
                'exit_code' => 2,
                'production' => true,
                'requires_confirmation' => true,
            ];
        }

        $lines = $this->orchestrator->executeSelection($dryRun, $database, $migrationNames);

        return [
            'lines' => $lines,
            'exit_code' => 0,
            'production' => App::environment('production'),
            'requires_confirmation' => false,
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     source_label: string,
     *     source_key: string,
     *     label: string,
     *     executed: bool,
     *     status: string
     * }
     */
    private function serializeEntry(SmartMigrationEntry $entry): array
    {
        return [
            'name' => $entry->name,
            'source_label' => $entry->sourceLabel,
            'source_key' => $entry->sourceKey,
            'label' => $entry->displayLabel(),
            'executed' => $entry->executed,
            'status' => $entry->statusKey(),
        ];
    }
}
