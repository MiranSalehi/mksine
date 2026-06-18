<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Inspection;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;

final class DatabaseSchemaInspector
{
    /** @var array<string, array<string, mixed>> */
    private array $tableColumnCache = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $tableIndexCache = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $tableForeignKeyCache = [];

    public function __construct(
        private readonly Builder $schema,
        private readonly Connection $connection,
    ) {}

    public function hasTable(string $table): bool
    {
        return $this->schema->hasTable($table);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function columns(string $table): array
    {
        if (! isset($this->tableColumnCache[$table])) {
            $this->tableColumnCache[$table] = $this->schema->getColumns($table);
        }

        return $this->tableColumnCache[$table];
    }

    public function hasColumn(string $table, string $column): bool
    {
        foreach ($this->columns($table) as $definition) {
            if (strcasecmp((string) ($definition['name'] ?? ''), $column) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function column(string $table, string $column): ?array
    {
        foreach ($this->columns($table) as $definition) {
            if (strcasecmp((string) ($definition['name'] ?? ''), $column) === 0) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function indexes(string $table): array
    {
        if (! isset($this->tableIndexCache[$table])) {
            $this->tableIndexCache[$table] = $this->schema->getIndexes($table);
        }

        return $this->tableIndexCache[$table];
    }

    public function hasIndex(string $table, string $indexName, array $columns = [], bool $unique = false): bool
    {
        foreach ($this->indexes($table) as $index) {
            $name = (string) ($index['name'] ?? '');
            $indexColumns = array_values(array_map(strval(...), (array) ($index['columns'] ?? [])));
            $isUnique = (bool) ($index['unique'] ?? false);

            if ($indexName !== '' && strcasecmp($name, $indexName) === 0) {
                return true;
            }

            if ($columns !== [] && $indexColumns === $columns && $isUnique === $unique) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function foreignKeys(string $table): array
    {
        if (! isset($this->tableForeignKeyCache[$table])) {
            $this->tableForeignKeyCache[$table] = $this->schema->getForeignKeys($table);
        }

        return $this->tableForeignKeyCache[$table];
    }

    public function hasForeignKey(string $table, string $name, array $columns = []): bool
    {
        foreach ($this->foreignKeys($table) as $foreignKey) {
            $fkName = (string) ($foreignKey['name'] ?? '');
            $fkColumns = array_values(array_map(strval(...), (array) ($foreignKey['columns'] ?? [])));

            if ($name !== '' && strcasecmp($fkName, $name) === 0) {
                return true;
            }

            if ($columns !== [] && $fkColumns === $columns) {
                return true;
            }
        }

        return false;
    }
}
