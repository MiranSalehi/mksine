<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support\AdminMenu;

use Filament\Support\Contracts\HasLabel;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;

/**
 * Where an admin sidebar item ends up after {@see AdminMenuOverrides} runs.
 */
enum AdminMenuPlacement: string implements HasLabel
{
    /** Keep the group the page/resource chose in code. */
    case Default = 'default';

    /** Remove from the sidebar only; the URL and canAccess() are untouched. */
    case Hidden = 'hidden';

    /** Move into another {@see AdminNavigationGroup}. */
    case Group = 'group';

    /** Remove from the sidebar and link it from the Settings cluster sub-navigation. */
    case Settings = 'settings';

    public function getLabel(): string
    {
        return __("mksine::admin_menu.placements.{$this->value}");
    }

    /**
     * Placements that take the item out of the sidebar.
     */
    public function removesFromSidebar(): bool
    {
        return $this === self::Hidden || $this === self::Settings;
    }
}
