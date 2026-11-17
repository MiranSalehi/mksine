<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Shape of form inputs.
 */
enum AppearanceField: string
{
    case Filled = 'filled';
    case Outlined = 'outlined';
    case Underline = 'underline';
    case Floating = 'floating';
}
