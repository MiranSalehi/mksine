<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Filament\Resources\GeoStates\Pages\EditGeoState;
use Miran\Mksine\Filament\Resources\GeoStates\Pages\ListGeoStates;
use Miran\Mksine\Filament\Resources\GeoStates\RelationManagers\CitiesRelationManager;
use Miran\Mksine\Filament\Resources\GeoStates\Schemas\GeoStateForm;
use Miran\Mksine\Filament\Resources\GeoStates\Tables\GeoStateTable;
use Miran\Mksine\Models\GeoState;
use Miran\Mksine\Services\Geo\StoreGeoSettings;

class GeoStateResource extends Resource
{
    protected static ?string $model = GeoState::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('mksine::geo.states.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::geo.states.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::geo.states.plural_model_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminSidebarNavigation::toolsGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return GeoStateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GeoStateTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'cities' => CitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeoStates::route('/'),
            'edit' => EditGeoState::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<GeoState>
     */
    public static function getEloquentQuery(): Builder
    {
        $ids = app(StoreGeoSettings::class)->enabledCountryIds();

        return parent::getEloquentQuery()
            ->with('country')
            ->when($ids !== [], fn (Builder $q) => $q->whereIn('geo_country_id', $ids));
    }
}
