<?php

namespace Miran\Mksine\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookManager;

class CategoryTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('categoryImage.full_url')
                    ->label(__('mksine::categories.image'))
                    ->circular()
                    ->size(50),
                TextColumn::make('name')
                    ->label(__('mksine::categories.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(50),
                TextColumn::make('slug')
                    ->label(__('mksine::categories.slug'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('parent.name')
                    ->label(__('mksine::categories.parent'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->color('gray'),
                TextColumn::make('posts_count')
                    ->label(__('mksine::categories.posts_count'))
                    ->counts('posts')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('mksine::categories.order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
                TextColumn::make('is_active')
                    ->label(__('mksine::categories.active'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? __('mksine::common.yes') : __('mksine::common.no'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('mksine::categories.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('mksine::categories.active'))
                    ->placeholder(__('mksine::common.all'))
                    ->trueLabel(__('mksine::categories.active_only'))
                    ->falseLabel(__('mksine::categories.inactive_only'))
                    ->native(false),
                SelectFilter::make('parent_id')
                    ->label(__('mksine::categories.parent_category'))
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
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
            ->defaultSort('sort_order', 'asc');

        // Apply table hooks
        $tableHookManager = app(TableHookManager::class);

        return $tableHookManager->apply('category.table', $table);
    }
}
