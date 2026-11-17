<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Corner radius scale.
 */
enum AppearanceRadius: string
{
    case Sharp = 'sharp';
    case Medium = 'medium';
    case Round = 'round';
}
