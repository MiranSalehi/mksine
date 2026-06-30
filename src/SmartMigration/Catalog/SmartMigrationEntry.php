<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Catalog;

final readonly class SmartMigrationEntry
{
    public function __construct(
        public string $name,
        public string $path,
        public string $sourceKey,
        public string $sourceLabel,
        public bool $executed,
    ) {}

    public function displayLabel(): string
    {
        $status = $this->executed ? 'ran' : 'pending';

        return sprintf('[%s] %s — %s', $this->sourceLabel, $this->name, $status);
    }

    public function statusKey(): string
    {
        return $this->executed ? 'ran' : 'pending';
    }
}
