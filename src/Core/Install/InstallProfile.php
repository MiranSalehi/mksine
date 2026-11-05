<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

/**
 * Where MKSine is being installed. Shared hosting has no shell, no long-running workers
 * and a short execution time, so some steps behave differently there.
 */
enum InstallProfile: string
{
    case Vps = 'vps';
    case Shared = 'shared';

    public function label(): string
    {
        return __('mksine::install.profiles.' . $this->value);
    }
}
