<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings\Concerns;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Miran\Mksine\Filament\Support\AdminMenu\MksineNavigationManager;

/**
 * Adds the sidebar items a super admin moved to "Settings" to the Settings cluster sub-navigation.
 * They stay plain links: the pages keep their own URL, cluster and access rules.
 */
trait HasAdminMenuSettingsLinks
{
    /**
     * @return array<NavigationItem | NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        $manager = app(NavigationManager::class);

        if (! $manager instanceof MksineNavigationManager) {
            return parent::getSubNavigation();
        }

        return [
            ...parent::getSubNavigation(),
            ...array_map(fn (NavigationItem $item): NavigationItem => clone $item, $manager->getSettingsItems()),
        ];
    }
}
