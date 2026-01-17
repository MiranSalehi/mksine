<?php

namespace Miran\Mksine\Filament\Resources\MenuLocations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Filament\Resources\MenuLocations\Pages\EditMenuLocation;
use Miran\Mksine\Filament\Resources\MenuLocations\Pages\ListMenuLocations;
use Miran\Mksine\Filament\Resources\MenuLocations\Schemas\MenuLocationForm;
use Miran\Mksine\Filament\Resources\MenuLocations\Tables\MenuLocationTable;
use Miran\Mksine\Models\MenuLocation;

class MenuLocationResource extends Resource
{
    protected static ?string $model = MenuLocation::class;

    protected static ?string $navigationLabel = 'Menu Locations';

    protected static ?string $modelLabel = 'Location';

    protected static ?string $pluralModelLabel = 'Locations';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Appearance');
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
}
