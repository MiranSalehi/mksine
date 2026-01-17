<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\Menus\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Filament\Pages\MenuBuilder;

class MenuTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('Slug copied')),

                TextColumn::make('items_count')
                    ->label(__('Items'))
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('locations.label')
                    ->label(__('Locations'))
                    ->badge()
                    ->separator(','),

                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                Action::make('builder')
                    ->label(__('Edit Items'))
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->url(fn ($record) => MenuBuilder::getUrl(['menu' => $record->id])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);

        // Apply table hooks for extensibility
        $tableHookManager = app(TableHookManager::class);

        return $tableHookManager->apply('menu.table', $table);
    }
}
