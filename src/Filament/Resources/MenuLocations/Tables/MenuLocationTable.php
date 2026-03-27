<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\MenuLocations\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuLocationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label(__('mksine::menu_locations.key'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label(__('mksine::menu_locations.label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('menu.name')
                    ->label(__('mksine::menu_locations.assigned_menu'))
                    ->placeholder(__('mksine::menu_locations.not_assigned'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(__('mksine::menu_locations.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('label')
            ->actions([
                EditAction::make(),
            ]);
    }
}
