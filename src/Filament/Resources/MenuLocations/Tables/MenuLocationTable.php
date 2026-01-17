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
                    ->label(__('Key'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label(__('Label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('menu.name')
                    ->label(__('Assigned Menu'))
                    ->placeholder(__('— Not assigned —'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
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
