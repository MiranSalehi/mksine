<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Support\Facades\FilamentAsset;

/**
 * Injects MKSine admin CSS after the Filament panel theme so Tailwind utilities are not overridden.
 */
final class MksinePanelStyles
{
    public static function renderAfterTheme(): string
    {
        try {
            $href = FilamentAsset::getStyleHref('mksine-styles', 'miran/mksine');
        } catch (\LogicException) {
            return '';
        }

        if ($href === '') {
            return '';
        }

        return '<link href="'.e($href).'" rel="stylesheet" data-navigate-track />';
    }
}
