<?php

namespace Miran\Mksine\Filament\Resources\Tags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookManager;

class TagTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('mksine::tags.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(50),
                TextColumn::make('slug')
                    ->label(__('mksine::tags.slug'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('posts_count')
                    ->label(__('mksine::tags.posts_count'))
                    ->counts('posts')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('pages_count')
                    ->label(__('mksine::tags.pages_count'))
                    ->counts('pages')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('mksine::tags.active'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? __('mksine::common.yes') : __('mksine::common.no'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('mksine::tags.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('mksine::tags.active'))
                    ->placeholder(__('mksine::common.all'))
                    ->trueLabel(__('mksine::tags.active_only'))
                    ->falseLabel(__('mksine::tags.inactive_only'))
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');

        $tableHookManager = app(TableHookManager::class);

        return $tableHookManager->apply('tag.table', $table);
    }
}
