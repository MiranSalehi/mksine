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
                    ->label(__('mksine::menus.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('mksine::menus.slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('mksine::menus.slug_copied')),

                TextColumn::make('items_count')
                    ->label(__('mksine::menus.items'))
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('locations.label')
                    ->label(__('mksine::menus.locations'))
                    ->badge()
                    ->separator(','),

                TextColumn::make('updated_at')
                    ->label(__('mksine::menus.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                Action::make('builder')
                    ->label(__('mksine::menus.edit_items'))
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
