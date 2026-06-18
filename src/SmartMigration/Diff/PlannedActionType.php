<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Diff;

enum PlannedActionType: string
{
    case CreateTable = 'create_table';
    case AddColumn = 'add_column';
    case AddIndex = 'add_index';
    case AddForeignKey = 'add_foreign_key';
}
