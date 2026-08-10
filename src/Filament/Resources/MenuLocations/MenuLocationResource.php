<?php

namespace Miran\Mksine\Filament\Resources\MenuLocations;

use BackedEnum;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Filament\Resources\MenuLocations\Pages\EditMenuLocation;
use Miran\Mksine\Filament\Resources\MenuLocations\Pages\ListMenuLocations;
use Miran\Mksine\Filament\Resources\MenuLocations\Schemas\MenuLocationForm;
use Miran\Mksine\Filament\Resources\MenuLocations\Tables\MenuLocationTable;
use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Core\Theme\ThemeBootstrap;
use Miran\Mksine\Models\MenuLocation;

class MenuLocationResource extends Resource
{
    protected static ?string $model = MenuLocation::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('mksine::menu_locations.navigation_label_cms');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::appearanceGroup();
    }

    public static function getModelLabel(): string
    {
        return __('mksine::menu_locations.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::menu_locations.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return MenuLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuLocationTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuLocations::route('/'),
            'edit' => EditMenuLocation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Locations are registered via code
    }

    /**
     * Only list menu locations registered by the active theme's theme.php (and any
     * other code that ran ThemeBootstrap). When the registry is empty (theme without
     * theme.php), fall back to all DB rows so legacy installs stay manageable.
     */
    public static function getEloquentQuery(): Builder
    {
        app(ThemeBootstrap::class)->boot();

        $query = parent::getEloquentQuery();
        $keys = array_keys(app(MenuLocationManager::class)->getLocations());

        if ($keys !== []) {
            return $query->whereIn('key', $keys);
        }

        return $query;
    }
}
