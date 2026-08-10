<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Plugins\PluginManager;

/**
 * Admin sidebar groups for WordPress-style parent → child navigation.
 *
 * Contract for core + plugins:
 * - Return {@see AdminNavigationGroup} from getNavigationGroup() (not translated strings)
 * - 1 item in a group ⇒ top-level “solo” parent (no chevron; click opens that item)
 * - 2+ items in the same group ⇒ hover flyout + chevron (plugins can add children later)
 * - Leave getNavigationGroup() null for true leaf top-level items (Dashboard, Plugins, Settings, …)
 *
 * Open-sidebar flyouts are handled in admin CSS/JS; collapsed sidebar keeps Filament dropdowns.
 */
final class AdminSidebarNavigation
{
    public const string GROUP_MEDIA = 'media';

    public const string GROUP_MENUS = 'menus';

    public const string GROUP_PRODUCTS = 'products';

    public const string GROUP_ORDERS = 'orders';

    public const string GROUP_STORE_SETTINGS = 'store_settings';

    public const string GROUP_USERS = 'users';

    public const string GROUP_THEME_PLUGINS = 'theme_plugins';

    public const string GROUP_CONTENT = 'content';

    public const string GROUP_SYSTEM = 'system';

    public const string GROUP_TOOLS = 'tools';

    public const string GROUP_APPEARANCE = 'appearance';

    public static function usesShopSidebar(): bool
    {
        try {
            if (! app()->bound(PluginManager::class) || ! Schema::hasTable('mks_plugins')) {
                return false;
            }

            return app(PluginManager::class)->getRegistry()->isActive('ecom');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Preferred group identity for Filament resources/pages (locale-safe).
     */
    public static function case(string $key): AdminNavigationGroup
    {
        return AdminNavigationGroup::from($key);
    }

    /**
     * @deprecated Prefer {@see case()} so icons survive locale changes.
     */
    public static function group(string $key): string
    {
        return self::case($key)->getLabel();
    }

    /**
     * @return list<string>
     */
    public static function shopGroupKeys(): array
    {
        return [
            self::GROUP_MEDIA,
            self::GROUP_CONTENT,
            self::GROUP_ORDERS,
            self::GROUP_PRODUCTS,
            self::GROUP_APPEARANCE,
            self::GROUP_STORE_SETTINGS,
            self::GROUP_USERS,
            self::GROUP_TOOLS,
            self::GROUP_SYSTEM,
        ];
    }

    /**
     * @return list<string>
     */
    public static function cmsGroupKeys(): array
    {
        return [
            self::GROUP_MEDIA,
            self::GROUP_CONTENT,
            self::GROUP_APPEARANCE,
            AdminNavigationGroup::AccessControl->value,
            self::GROUP_TOOLS,
            self::GROUP_SYSTEM,
        ];
    }

    /**
     * @return list<AdminNavigationGroup>
     */
    public static function orderedCases(): array
    {
        $keys = self::usesShopSidebar() ? self::shopGroupKeys() : self::cmsGroupKeys();

        return array_map(
            fn (string $key): AdminNavigationGroup => AdminNavigationGroup::from($key),
            $keys,
        );
    }

    /**
     * @return list<string>
     */
    public static function cmsOrderedGroupLabels(): array
    {
        return array_map(
            fn (AdminNavigationGroup $case): string => $case->getLabel(),
            array_map(
                fn (string $key): AdminNavigationGroup => AdminNavigationGroup::from($key),
                self::cmsGroupKeys(),
            ),
        );
    }

    /**
     * @return list<string>
     */
    public static function shopOrderedGroupLabels(): array
    {
        return array_map(
            fn (string $key): string => AdminNavigationGroup::from($key)->getLabel(),
            self::shopGroupKeys(),
        );
    }

    /**
     * @return list<string>
     */
    public static function orderedGroupLabels(): array
    {
        return array_map(
            fn (AdminNavigationGroup $case): string => $case->getLabel(),
            self::orderedCases(),
        );
    }

    public static function contentGroup(): AdminNavigationGroup
    {
        return AdminNavigationGroup::Content;
    }

    public static function appearanceGroup(): AdminNavigationGroup
    {
        return AdminNavigationGroup::Appearance;
    }

    public static function accessControlGroup(): AdminNavigationGroup
    {
        return AdminNavigationGroup::AccessControl;
    }

    public static function systemGroup(): AdminNavigationGroup
    {
        return AdminNavigationGroup::System;
    }

    public static function toolsGroup(): AdminNavigationGroup
    {
        return AdminNavigationGroup::Tools;
    }

    /**
     * @return array<string, NavigationGroup>
     */
    public static function panelGroups(): array
    {
        $groups = [];

        foreach (self::orderedCases() as $case) {
            // Key by enum name so Filament matches UnitEnum navigation groups.
            // Label must be a Closure: register() may freeze strings before Language Switch
            // sets the request locale, which mixed Persian parents with English children.
            $groups[$case->name] = NavigationGroup::make()
                ->label(fn (): string => $case->getLabel())
                ->icon($case->getIcon())
                ->collapsed()
                ->collapsible();
        }

        return $groups;
    }
}
