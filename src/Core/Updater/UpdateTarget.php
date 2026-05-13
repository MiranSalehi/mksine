<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Identifies which subsystem a ZIP update targets.
 *
 * Used by the UI, CLI, and logging to disambiguate pipeline variants.
 */
enum UpdateTarget: string
{
    case Plugin = 'plugin';

    case Theme = 'theme';

    case Core = 'core';

    public function label(): string
    {
        return match ($this) {
            self::Plugin => 'plugin',
            self::Theme => 'theme',
            self::Core => 'core',
        };
    }
}
