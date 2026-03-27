<?php

namespace Miran\Mksine\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Miran\Mksine\Core\Hooks\TableHookManager;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('featuredImage.full_url')
                    ->circular()
                    ->label(__('mksine::posts.image')),
                TextColumn::make('title')
                    ->label(__('mksine::posts.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(50),
                TextColumn::make('slug')
                    ->label(__('mksine::posts.slug'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('mksine::posts.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        'archived' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => __('mksine::posts.status_draft'),
                        'published' => __('mksine::posts.status_published'),
                        'archived' => __('mksine::posts.status_archived'),
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label(__('mksine::posts.author'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label(__('mksine::posts.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('views_count')
                    ->label(__('mksine::posts.views'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label(__('mksine::posts.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('mksine::posts.status'))
                    ->options([
                        'draft' => __('mksine::posts.status_draft'),
                        'published' => __('mksine::posts.status_published'),
                        'archived' => __('mksine::posts.status_archived'),
                    ])
                    ->native(false),
                Filter::make('published_at')
                    ->label(__('mksine::posts.published_at'))
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('published_from')
                            ->label(__('mksine::posts.published_from')),
                        \Filament\Forms\Components\DatePicker::make('published_until')
                            ->label(__('mksine::posts.published_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date),
                            );
                    }),
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
            ->defaultSort('created_at', 'desc');

        // Apply table hooks
        $tableHookManager = app(TableHookManager::class);

        return $tableHookManager->apply('post.table', $table);
    }
}
