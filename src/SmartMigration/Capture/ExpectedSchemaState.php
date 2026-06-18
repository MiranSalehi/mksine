<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

final class ExpectedSchemaState
{
    /** @var array<string, ExpectedTable> */
    private array $tables = [];

    public function table(string $name): ExpectedTable
    {
        if (! isset($this->tables[$name])) {
            $this->tables[$name] = new ExpectedTable($name);
        }

        return $this->tables[$name];
    }

    /**
     * @return array<string, ExpectedTable>
     */
    public function tables(): array
    {
        return $this->tables;
    }
}
