<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;
use Miran\Mksine\SmartMigration\Support\ColumnTypeNormalizer;

final class BlueprintSchemaProcessor
{
    /**
     * @return list<string>
     */
    public function apply(ExpectedSchemaState $state, Blueprint $blueprint): array
    {
        $warnings = [];
        $table = $blueprint->getTable();
        $expectedTable = $state->table($table);

        foreach ($blueprint->getColumns() as $column) {
            if (! $column instanceof ColumnDefinition) {
                continue;
            }

            if ((bool) ($column->change ?? false)) {
                $warnings[] = "[WARNING] Unsupported migration operation detected: column change on {$table}.{$column->name}. Manual review required.";

                continue;
            }

            $normalized = ColumnTypeNormalizer::fromBlueprintColumn($column);
            $expectedTable->setColumn(new ExpectedColumn(
                name: (string) $column->name,
                type: $normalized['type'],
                attributes: $normalized['attributes'],
            ));
        }

        foreach ($blueprint->getCommands() as $command) {
            if (! $command instanceof Fluent) {
                continue;
            }

            $warnings = array_merge($warnings, $this->processCommand($expectedTable, $command));
        }

        return $warnings;
    }

    /**
     * @return list<string>
     */
    private function processCommand(ExpectedTable $table, Fluent $command): array
    {
        $warnings = [];

        return match ($command->name) {
            'index' => $this->registerIndex($table, $command, unique: false),
            'unique' => $this->registerIndex($table, $command, unique: true),
            'primary' => $this->registerIndex($table, $command, unique: true, primary: true),
            'foreign' => $this->registerForeignKey($table, $command),
            'drop', 'dropColumn', 'dropForeign', 'dropIndex', 'dropUnique', 'dropPrimary', 'rename' => [
                "[WARNING] Unsupported migration operation detected: {$command->name} on {$table->name}. Manual review required.",
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function registerIndex(ExpectedTable $table, Fluent $command, bool $unique, bool $primary = false): array
    {
        $columns = array_values(array_map(strval(...), (array) ($command->columns ?? [])));

        if ($columns === []) {
            return [];
        }

        $name = (string) ($command->index ?? $this->buildIndexName($table->name, $columns, $unique, $primary));

        $table->setIndex(new ExpectedIndex(
            name: $name,
            columns: $columns,
            unique: $unique,
            primary: $primary,
        ));

        return [];
    }

    /**
     * @return list<string>
     */
    private function registerForeignKey(ExpectedTable $table, Fluent $command): array
    {
        $columns = array_values(array_map(strval(...), (array) ($command->columns ?? [])));
        $references = array_values(array_map(strval(...), (array) ($command->references ?? [])));
        $on = (string) ($command->on ?? '');

        if ($columns === [] || $references === [] || $on === '') {
            return [];
        }

        $name = (string) ($command->index ?? $this->buildForeignKeyName($table->name, $columns));

        $table->setForeignKey(new ExpectedForeignKey(
            name: $name,
            columns: $columns,
            referencedTable: $on,
            references: $references,
            onDelete: isset($command->onDelete) ? (string) $command->onDelete : null,
            onUpdate: isset($command->onUpdate) ? (string) $command->onUpdate : null,
        ));

        return [];
    }

    /**
     * @param  list<string>  $columns
     */
    private function buildIndexName(string $table, array $columns, bool $unique, bool $primary): string
    {
        if ($primary) {
            return 'primary';
        }

        $suffix = implode('_', $columns).($unique ? '_unique' : '_index');

        return "{$table}_{$suffix}";
    }

    /**
     * @param  list<string>  $columns
     */
    private function buildForeignKeyName(string $table, array $columns): string
    {
        return "{$table}_".implode('_', $columns).'_foreign';
    }
}
