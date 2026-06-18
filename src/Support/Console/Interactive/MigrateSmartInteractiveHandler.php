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
     *     migrations: list<array{name: string, source_label: string, source_key: string, label: string}>
     * }
     */
    public function catalog(): array
    {
        return [
            'count' => count($this->catalog->executedEntries()),
            'migrations' => array_map(
                fn (SmartMigrationEntry $entry): array => $this->serializeEntry($entry),
                $this->catalog->executedEntries(),
            ),
        ];
    }

    /**
     * @param  list<string>  $migrationNames
     * @return array{
     *     actions: list<array{id: string, label: string}>,
     *     notices: list<string>,
     *     warnings: list<string>
     * }
     */
    public function analyze(array $migrationNames, ?string $database = null): array
    {
        $plan = $this->orchestrator->analyze($database, null, $migrationNames);

        return [
            'actions' => array_map(
                fn (PlannedAction $action): array => [
                    'id' => $action->id,
                    'label' => $action->label(),
                ],
                $plan['actions'],
            ),
            'notices' => $plan['notices'],
            'warnings' => $plan['warnings'],
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

        $lines = $this->orchestrator->execute($dryRun, $database, null, $migrationNames);

        return [
            'lines' => $lines,
            'exit_code' => 0,
            'production' => App::environment('production'),
            'requires_confirmation' => false,
        ];
    }

    /**
     * @return array{name: string, source_label: string, source_key: string, label: string}
     */
    private function serializeEntry(SmartMigrationEntry $entry): array
    {
        return [
            'name' => $entry->name,
            'source_label' => $entry->sourceLabel,
            'source_key' => $entry->sourceKey,
            'label' => $entry->displayLabel(),
        ];
    }
}
