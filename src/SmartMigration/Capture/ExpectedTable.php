<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

final class ExpectedTable
{
    /** @var array<string, ExpectedColumn> */
    private array $columns = [];

    /** @var array<string, ExpectedIndex> */
    private array $indexes = [];

    /** @var array<string, ExpectedForeignKey> */
    private array $foreignKeys = [];

    public function __construct(
        public readonly string $name,
    ) {}

    public function setColumn(ExpectedColumn $column): void
    {
        $this->columns[$column->name] = $column;
    }

    public function setIndex(ExpectedIndex $index): void
    {
        $this->indexes[$index->name] = $index;
    }

    public function setForeignKey(ExpectedForeignKey $foreignKey): void
    {
        $this->foreignKeys[$foreignKey->name] = $foreignKey;
    }

    /**
     * @return array<string, ExpectedColumn>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<string, ExpectedIndex>
     */
    public function indexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return array<string, ExpectedForeignKey>
     */
    public function foreignKeys(): array
    {
        return $this->foreignKeys;
    }
}
