<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Diff;

use Miran\Mksine\SmartMigration\Capture\ExpectedColumn;
use Miran\Mksine\SmartMigration\Capture\ExpectedForeignKey;
use Miran\Mksine\SmartMigration\Capture\ExpectedIndex;
use Miran\Mksine\SmartMigration\Capture\ExpectedTable;

final class PlannedAction
{
    public function __construct(
        public readonly string $id,
        public readonly PlannedActionType $type,
        public readonly string $table,
        public readonly ExpectedTable|ExpectedColumn|ExpectedIndex|ExpectedForeignKey $payload,
        public readonly ?string $sqlHint = null,
    ) {}

    public function label(): string
    {
        return match ($this->type) {
            PlannedActionType::CreateTable => 'table '.$this->table,
            PlannedActionType::AddColumn => 'column '.$this->table.'.'.($this->payload instanceof ExpectedColumn ? $this->payload->name : ''),
            PlannedActionType::AddIndex => 'index '.$this->table.'.'.($this->payload instanceof ExpectedIndex ? $this->payload->name : ''),
            PlannedActionType::AddForeignKey => 'foreign key '.$this->table.'.'.($this->payload instanceof ExpectedForeignKey ? $this->payload->name : ''),
        };
    }
}
