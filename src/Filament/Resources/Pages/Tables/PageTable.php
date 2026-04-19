<?php

namespace Miran\Mksine\Filament\Resources\Pages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Models\Page;

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
                Action::make('duplicate')
                    ->label(__('mksine::pages.duplicate'))
                    ->icon(Heroicon::Square2Stack)
                    ->color('gray')
                    ->authorize('replicate')
                    ->requiresConfirmation()
                    ->modalHeading(__('mksine::pages.duplicate_modal_heading'))
                    ->modalDescription(__('mksine::pages.duplicate_modal_description'))
                    ->action(function (Page $record, Action $action): void {
                        $duplicate = $record->duplicateAsDraft(Auth::id());
                        Notification::make()
                            ->title(__('mksine::pages.duplicate_success'))
                            ->success()
                            ->send();
                        $action->redirect(PageResource::getUrl('edit', ['record' => $duplicate]));
                    }),
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
