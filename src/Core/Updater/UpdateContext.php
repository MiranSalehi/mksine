<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Mutable accumulator for a single update run. Written incrementally so
 * failure results still carry version and backup path after a mid-pipeline throw.
 */
final class UpdateContext
{
    public ?string $fromVersion = null;

    public ?string $toVersion = null;

    public ?string $backupPath = null;

    public bool $swapped = false;

    public bool $dbPossiblyDirty = false;

    /** @var list<string> */
    public array $steps = [];

    /** @var list<string> */
    public array $warnings = [];
}
