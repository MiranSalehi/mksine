<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Stable admin sidebar groups (locale-independent identity + icons).
 *
 * Resources/pages/plugins must return these enum cases from getNavigationGroup(),
 * not translated strings — otherwise icons break when the UI locale differs from
 * the locale used when navigation groups were first registered.
 */
enum AdminNavigationGroup: string implements HasLabel, HasIcon
{
    case Media = 'media';
    case Content = 'content';
    case Orders = 'orders';
    case Products = 'products';
    case Appearance = 'appearance';
    case StoreSettings = 'store_settings';
    case Users = 'users';
    case AccessControl = 'access_control';
    case Tools = 'tools';
    case System = 'system';
    case Menus = 'menus';
    case ThemePlugins = 'theme_plugins';

    public function getLabel(): string
    {
        return match ($this) {
            self::Media => __('mksine::common.media_group'),
            self::Content => __('mksine::common.content'),
            self::Orders => self::ecomLabel('orders', 'Orders'),
            self::Products => self::ecomLabel('products', 'Products'),
            self::Appearance => __('mksine::common.appearance'),
            self::StoreSettings => self::ecomLabel('store_settings', 'Store settings'),
            self::Users => __('mksine::common.users_group'),
            self::AccessControl => __('mksine::common.access_control'),
            self::Tools => __('mksine::common.tools'),
            self::System => __('mksine::common.system'),
            self::Menus => __('mksine::common.menus_group'),
            self::ThemePlugins => __('mksine::common.theme_plugins_group'),
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Media => Heroicon::OutlinedPhoto,
            self::Content => Heroicon::OutlinedDocumentText,
            self::Orders => Heroicon::OutlinedShoppingCart,
            self::Products => Heroicon::OutlinedShoppingBag,
            self::Appearance, self::Menus, self::ThemePlugins => Heroicon::OutlinedPaintBrush,
            self::StoreSettings => Heroicon::OutlinedCog6Tooth,
            self::Users, self::AccessControl => Heroicon::OutlinedUsers,
            self::Tools => Heroicon::OutlinedWrenchScrewdriver,
            self::System => Heroicon::OutlinedCog8Tooth,
        };
    }

    private static function ecomLabel(string $key, string $fallback): string
    {
        $translated = __("ecom::admin.navigation.{$key}");

        return $translated !== "ecom::admin.navigation.{$key}" ? $translated : $fallback;
    }
}
