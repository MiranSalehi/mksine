<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support\AdminMenu;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

/**
 * Admin sidebar after {@see AdminMenuOverrides::apply()}.
 */
final readonly class AdminMenuResolution
{
    /**
     * @param  list<NavigationGroup>  $groups  Sidebar groups in display order (empty groups removed).
     * @param  list<NavigationItem>  $settingsItems  Items moved out of the sidebar into the Settings sub-navigation.
     */
    public function __construct(
        public array $groups,
        public array $settingsItems,
    ) {}
}
