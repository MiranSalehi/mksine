<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Light/dark mode of the panel; `System` follows `prefers-color-scheme`.
 */
enum AppearanceMode: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}
