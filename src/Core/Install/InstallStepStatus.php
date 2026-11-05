<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

enum InstallStepStatus: string
{
    case Completed = 'completed';
    case Skipped = 'skipped';

    /** The step made progress but must run again (e.g. migrations split across requests). */
    case Incomplete = 'incomplete';

    case Failed = 'failed';

    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Skipped;
    }
}
