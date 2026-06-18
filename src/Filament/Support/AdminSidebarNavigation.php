<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Plugins\PluginManager;

/**
 * Shop sidebar (Voltech / surawshop) when the ecom plugin is active; full CMS sidebar otherwise.
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

    public static function group(string $key): string
    {
        return match ($key) {
            self::GROUP_MEDIA => __('mksine::common.media_group'),
            self::GROUP_MENUS => __('mksine::common.menus_group'),
            self::GROUP_PRODUCTS => self::ecomNavigation('products', 'Products'),
            self::GROUP_ORDERS => self::ecomNavigation('orders', 'Orders'),
            self::GROUP_STORE_SETTINGS => self::ecomNavigation('store_settings', 'Store settings'),
            self::GROUP_USERS => __('mksine::common.users_group'),
            self::GROUP_THEME_PLUGINS => __('mksine::common.theme_plugins_group'),
            self::GROUP_CONTENT => self::contentGroup(),
            self::GROUP_SYSTEM => self::systemGroup(),
            default => __('mksine::common.media_group'),
        };
    }

    /**
     * @return list<string>
     */
    public static function cmsOrderedGroupLabels(): array
    {
        return [
            __('mksine::common.content'),
            __('mksine::common.appearance'),
            __('mksine::common.access_control'),
            __('mksine::common.system'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function shopGroupKeys(): array
    {
        return [
            self::GROUP_MEDIA,
            self::GROUP_MENUS,
            self::GROUP_PRODUCTS,
            self::GROUP_ORDERS,
            self::GROUP_CONTENT,
            self::GROUP_STORE_SETTINGS,
            self::GROUP_USERS,
            self::GROUP_SYSTEM,
            self::GROUP_THEME_PLUGINS,
        ];
    }

    /**
     * @return list<string>
     */
    public static function cmsGroupKeys(): array
    {
        return ['content', 'appearance', 'access_control', 'system'];
    }

    /**
     * @return list<string>
     */
    public static function shopOrderedGroupLabels(): array
    {
        return array_map(
            fn (string $key): string => self::group($key),
            self::shopGroupKeys(),
        );
    }

    /**
     * Ordered sidebar group labels — must match navigation item {@see group()} / CMS group strings.
     *
     * @return list<string>
     */
    public static function orderedGroupLabels(): array
    {
        return self::usesShopSidebar()
            ? self::shopOrderedGroupLabels()
            : self::cmsOrderedGroupLabels();
    }

    public static function contentGroup(): string
    {
        return __('mksine::common.content');
    }

    public static function appearanceGroup(): string
    {
        return __('mksine::common.appearance');
    }

    public static function accessControlGroup(): string
    {
        return __('mksine::common.access_control');
    }

    public static function systemGroup(): string
    {
        return __('mksine::common.system');
    }

    /**
     * Navigation groups keyed by label so Filament preserves Voltech / CMS sort order.
     *
     * @return array<string, NavigationGroup>
     */
    public static function panelGroups(): array
    {
        $groups = [];

        foreach (self::orderedGroupLabels() as $label) {
            $groups[$label] = NavigationGroup::make($label)
                ->collapsed(false)
                ->collapsible();
        }

        return $groups;
    }

    private static function ecomNavigation(string $key, string $fallback): string
    {
        $translated = __("ecom::admin.navigation.{$key}");

        return $translated !== "ecom::admin.navigation.{$key}" ? $translated : $fallback;
    }
}
