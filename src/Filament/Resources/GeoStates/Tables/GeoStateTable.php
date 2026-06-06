<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Miran\Mksine\Services\Geo\GeoResolver;

final class GeoStateTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.iso2')
                    ->label(__('mksine::geo.states.country'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('mksine::geo.states.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('native')
                    ->label(__('mksine::geo.states.native'))
                    ->searchable(),
                TextColumn::make('source')
                    ->label(__('mksine::geo.states.source'))
                    ->badge(),
                IconColumn::make('is_visible')
                    ->label(__('mksine::geo.states.is_visible'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('geo_country_id')
                    ->label(__('mksine::geo.states.country'))
                    ->options(fn (): array => app(GeoResolver::class)->countriesForSelect()),
            ])
            ->defaultSort('name');
    }
}
