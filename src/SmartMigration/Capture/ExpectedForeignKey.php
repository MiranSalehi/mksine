<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

final class ExpectedForeignKey
{
    /**
     * @param  list<string>  $columns
     * @param  list<string>  $references
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly string $referencedTable,
        public readonly array $references,
        public readonly ?string $onDelete = null,
        public readonly ?string $onUpdate = null,
    ) {}
}
