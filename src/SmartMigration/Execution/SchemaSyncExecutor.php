<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Execution;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Miran\Mksine\SmartMigration\Capture\ExpectedColumn;
use Miran\Mksine\SmartMigration\Capture\ExpectedForeignKey;
use Miran\Mksine\SmartMigration\Capture\ExpectedIndex;
use Miran\Mksine\SmartMigration\Capture\ExpectedTable;
use Miran\Mksine\SmartMigration\Diff\PlannedAction;
use Miran\Mksine\SmartMigration\Diff\PlannedActionType;
use Miran\Mksine\SmartMigration\Inspection\DatabaseSchemaInspector;

final class SchemaSyncExecutor
{
    public function __construct(
        private readonly Builder $schema,
        private readonly DatabaseSchemaInspector $inspector,
    ) {}

    public function describeSql(PlannedAction $action): ?string
    {
        return match ($action->type) {
            PlannedActionType::CreateTable => "CREATE TABLE {$action->table} (...)",
            PlannedActionType::AddColumn => "ALTER TABLE {$action->table} ADD COLUMN {$action->payload->name} ...",
            PlannedActionType::AddIndex => "CREATE INDEX {$action->payload->name} ON {$action->table}",
            PlannedActionType::AddForeignKey => "ALTER TABLE {$action->table} ADD CONSTRAINT {$action->payload->name} FOREIGN KEY (...)",
        };
    }

    public function execute(PlannedAction $action): void
    {
        match ($action->type) {
            PlannedActionType::CreateTable => $this->createTable($action->table, $action->payload),
            PlannedActionType::AddColumn => $this->addColumn($action->table, $action->payload),
            PlannedActionType::AddIndex => $this->addIndex($action->table, $action->payload),
            PlannedActionType::AddForeignKey => $this->addForeignKey($action->table, $action->payload),
        };
    }

    private function createTable(string $table, ExpectedTable $expectedTable): void
    {
        if ($this->inspector->hasTable($table)) {
            return;
        }

        $this->schema->create($table, function (Blueprint $blueprint) use ($expectedTable): void {
            foreach ($expectedTable->columns() as $column) {
                $this->applyColumnDefinition($blueprint, $column);
            }

            foreach ($expectedTable->indexes() as $index) {
                $this->applyIndexDefinition($blueprint, $index);
            }

            foreach ($expectedTable->foreignKeys() as $foreignKey) {
                $this->applyForeignKeyDefinition($blueprint, $foreignKey);
            }
        });
    }

    private function addColumn(string $table, ExpectedColumn $column): void
    {
        if ($this->inspector->hasColumn($table, $column->name)) {
            return;
        }

        if (! $this->inspector->hasTable($table)) {
            return;
        }

        $this->schema->table($table, function (Blueprint $blueprint) use ($column): void {
            $this->applyColumnDefinition($blueprint, $column);
        });
    }

    private function addIndex(string $table, ExpectedIndex $index): void
    {
        if ($this->inspector->hasIndex($table, $index->name, $index->columns, $index->unique)) {
            return;
        }

        $this->schema->table($table, function (Blueprint $blueprint) use ($index): void {
            $this->applyIndexDefinition($blueprint, $index);
        });
    }

    private function addForeignKey(string $table, ExpectedForeignKey $foreignKey): void
    {
        if ($this->inspector->hasForeignKey($table, $foreignKey->name, $foreignKey->columns)) {
            return;
        }

        $this->schema->table($table, function (Blueprint $blueprint) use ($foreignKey): void {
            $this->applyForeignKeyDefinition($blueprint, $foreignKey);
        });
    }

    private function applyColumnDefinition(Blueprint $blueprint, ExpectedColumn $column): void
    {
        $definition = $this->makeColumn($blueprint, $column);

        if (($column->attributes['nullable'] ?? false) === true) {
            $definition->nullable();
        }

        if (array_key_exists('default', $column->attributes)) {
            $definition->default($column->attributes['default']);
        }

        if (($column->attributes['unsigned'] ?? false) === true) {
            $definition->unsigned();
        }
    }

    private function makeColumn(Blueprint $blueprint, ExpectedColumn $column): \Illuminate\Database\Schema\ColumnDefinition
    {
        $name = $column->name;
        $length = $column->attributes['length'] ?? null;

        return match ($column->type) {
            'id' => $blueprint->id(),
            'bigIncrements', 'bigInteger' => ($column->attributes['autoIncrement'] ?? false)
                ? $blueprint->bigIncrements($name)
                : $blueprint->bigInteger($name),
            'integer', 'increments' => ($column->attributes['autoIncrement'] ?? false)
                ? $blueprint->increments($name)
                : $blueprint->integer($name),
            'text' => $blueprint->text($name),
            'longText' => $blueprint->longText($name),
            'boolean' => $blueprint->boolean($name),
            'json', 'jsonb' => $blueprint->json($name),
            'decimal' => $blueprint->decimal(
                $name,
                (int) ($column->attributes['precision'] ?? 8),
                (int) ($column->attributes['scale'] ?? 2),
            ),
            'float' => $blueprint->float($name),
            'double' => $blueprint->double($name),
            'date' => $blueprint->date($name),
            'datetime', 'timestamp' => $blueprint->dateTime($name),
            'time' => $blueprint->time($name),
            default => $blueprint->string($name, is_numeric($length) ? (int) $length : 255),
        };
    }

    private function applyIndexDefinition(Blueprint $blueprint, ExpectedIndex $index): void
    {
        if ($index->primary) {
            $blueprint->primary($index->columns);

            return;
        }

        if ($index->unique) {
            $blueprint->unique($index->columns, $index->name);

            return;
        }

        $blueprint->index($index->columns, $index->name);
    }

    private function applyForeignKeyDefinition(Blueprint $blueprint, ExpectedForeignKey $foreignKey): void
    {
        $definition = $blueprint->foreign($foreignKey->columns, $foreignKey->name)
            ->references($foreignKey->references)
            ->on($foreignKey->referencedTable);

        if ($foreignKey->onDelete !== null) {
            $definition->onDelete($foreignKey->onDelete);
        }

        if ($foreignKey->onUpdate !== null) {
            $definition->onUpdate($foreignKey->onUpdate);
        }
    }
}
