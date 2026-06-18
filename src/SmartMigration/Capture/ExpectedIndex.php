<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

final class ExpectedIndex
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly bool $unique = false,
        public readonly bool $primary = false,
    ) {}
}
