<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

final class CaptureSchemaBuilder extends Builder
{
    /** @var list<Blueprint> */
    private array $capturedBlueprints = [];

    /**
     * @return list<Blueprint>
     */
    public function capturedBlueprints(): array
    {
        return $this->capturedBlueprints;
    }

    public function flushCapturedBlueprints(): void
    {
        $this->capturedBlueprints = [];
    }

    /**
     * Record blueprint metadata without executing SQL.
     */
    public function build(Blueprint $blueprint): void
    {
        $this->capturedBlueprints[] = $blueprint;
    }
}
