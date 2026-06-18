<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

final class ExpectedColumn
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $attributes = [],
    ) {}
}
