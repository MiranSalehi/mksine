<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Diff;

use Miran\Mksine\SmartMigration\Capture\ExpectedSchemaState;
use Miran\Mksine\SmartMigration\Capture\ExpectedTable;
use Miran\Mksine\SmartMigration\Inspection\DatabaseSchemaInspector;
use Miran\Mksine\SmartMigration\Support\ColumnTypeNormalizer;

final class SchemaDiffPlanner
{
    public function __construct(
        private readonly DatabaseSchemaInspector $inspector,
    ) {}

    /**
     * @return array{actions: list<PlannedAction>, notices: list<string>, warnings: list<string>}
     */
    public function plan(ExpectedSchemaState $expected, array $captureWarnings = []): array
    {
        $actions = [];
        $notices = [];
        $warnings = $captureWarnings;

        foreach ($expected->tables() as $tableName => $expectedTable) {
            if (! $this->inspector->hasTable($tableName)) {
                $actions[] = new PlannedAction(
                    id: "create_table:{$tableName}",
                    type: PlannedActionType::CreateTable,
                    table: $tableName,
                    payload: $expectedTable,
                );

                continue;
            }

            $actualColumnNames = array_map(
                static fn (array $column): string => (string) $column['name'],
                $this->inspector->columns($tableName),
            );

            $expectedColumnNames = array_keys($expectedTable->columns());

            foreach ($expectedTable->columns() as $columnName => $expectedColumn) {
                if (! $this->inspector->hasColumn($tableName, $columnName)) {
                    $orphans = array_values(array_diff($actualColumnNames, $expectedColumnNames));

                    if (count($orphans) === 1) {
                        $notices[] = "[NOTICE] Possible column rename detected:\n{$tableName}.{$orphans[0]} -> {$tableName}.{$columnName}\nManual action required.";
                    }

                    $actions[] = new PlannedAction(
                        id: "add_column:{$tableName}.{$columnName}",
                        type: PlannedActionType::AddColumn,
                        table: $tableName,
                        payload: $expectedColumn,
                    );

                    continue;
                }

                $actual = $this->inspector->column($tableName, $columnName);

                if ($actual !== null && ! ColumnTypeNormalizer::typesMatch($expectedColumn->type, $expectedColumn->attributes, $actual)) {
                    $warnings[] = "[WARNING] Column type mismatch detected:\n{$tableName}.{$columnName}\nDatabase: ".($actual['type'] ?? 'unknown')."\nMigration: {$expectedColumn->type}\nManual migration required.";
                }
            }

            foreach ($expectedTable->indexes() as $index) {
                if ($this->inspector->hasIndex($tableName, $index->name, $index->columns, $index->unique)) {
                    continue;
                }

                $actions[] = new PlannedAction(
                    id: "add_index:{$tableName}.{$index->name}",
                    type: PlannedActionType::AddIndex,
                    table: $tableName,
                    payload: $index,
                );
            }

            foreach ($expectedTable->foreignKeys() as $foreignKey) {
                if ($this->inspector->hasForeignKey($tableName, $foreignKey->name, $foreignKey->columns)) {
                    continue;
                }

                $actions[] = new PlannedAction(
                    id: "add_foreign:{$tableName}.{$foreignKey->name}",
                    type: PlannedActionType::AddForeignKey,
                    table: $tableName,
                    payload: $foreignKey,
                );
            }
        }

        return [
            'actions' => $actions,
            'notices' => array_values(array_unique($notices)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }
}
