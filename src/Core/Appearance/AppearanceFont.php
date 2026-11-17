<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Text size, as a factor on the base font sizes.
 */
enum AppearanceFont: string
{
    case Small = 'small';
    case Normal = 'normal';
    case Large = 'large';
}
