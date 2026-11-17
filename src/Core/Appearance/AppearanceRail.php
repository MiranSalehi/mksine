<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Colour of the sidebar.
 */
enum AppearanceRail: string
{
    case Dark = 'dark';
    case Light = 'light';
    case Brand = 'brand';
}
