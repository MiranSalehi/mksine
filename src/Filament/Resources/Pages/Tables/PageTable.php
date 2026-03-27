<?php

namespace Miran\Mksine\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookManager;

class PageTable
{
    public static function configure(Table $table): Table
    {
        $tableHookManager = app(TableHookManager::class);

        $table = $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('mksine::pages.title'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug),
                TextColumn::make('type')
                    ->label(__('mksine::pages.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'simple' => __('mksine::pages.type_simple'),
                        'builder' => __('mksine::pages.type_builder'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'simple' => 'gray',
                        'builder' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('mksine::pages.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => __('mksine::pages.status_draft'),
                        'published' => __('mksine::pages.status_published'),
                        'scheduled' => __('mksine::pages.status_scheduled'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('mksine::pages.author'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label(__('mksine::pages.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('mksine::pages.updated'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('mksine::pages.type'))
                    ->options([
                        'simple' => __('mksine::pages.type_simple'),
                        'builder' => __('mksine::pages.type_builder'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('mksine::pages.status'))
                    ->options([
                        'draft' => __('mksine::pages.status_draft'),
                        'published' => __('mksine::pages.status_published'),
                        'scheduled' => __('mksine::pages.status_scheduled'),
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');

        // Apply table hooks
        return $tableHookManager->apply('page.table', $table);
    }
}
