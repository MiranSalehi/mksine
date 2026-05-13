<?php

namespace Miran\Mksine\Filament\Resources\Comments\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;

class CommentTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('commentable.title')
                    ->label(__('mksine::comments.commentable'))
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Comment $record): string => (string) ($record->commentable?->title ?? '')),
                TextColumn::make('author_display_name')
                    ->label(__('mksine::comments.author'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('author_name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->orderByRaw("COALESCE(author_name, (SELECT name FROM users WHERE users.id = comments.user_id)) {$direction}");
                    }),
                TextColumn::make('content')
                    ->label(__('mksine::comments.content'))
                    ->searchable()
                    ->limit(80)
                    ->wrap()
                    ->html()
                    ->formatStateUsing(fn (Comment $record): string => nl2br(e(\Illuminate\Support\Str::limit($record->content, 80)))),
                TextColumn::make('rating')
                    ->label(__('mksine::comments.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : '-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('mksine::comments.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Comment::STATUS_APPROVED => __('mksine::comments.status_approved'),
                        Comment::STATUS_PENDING => __('mksine::comments.status_pending'),
                        Comment::STATUS_SPAM => __('mksine::comments.status_spam'),
                        Comment::STATUS_TRASH => __('mksine::comments.status_trash'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Comment::STATUS_APPROVED => 'success',
                        Comment::STATUS_PENDING => 'warning',
                        Comment::STATUS_SPAM => 'danger',
                        Comment::STATUS_TRASH => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('parent_id')
                    ->label(__('mksine::comments.type'))
                    ->formatStateUsing(fn (?int $state): string => $state ? __('mksine::comments.reply_type') : __('mksine::comments.root_comment'))
                    ->badge()
                    ->color(fn (?int $state): string => $state ? 'info' : 'primary'),
                TextColumn::make('replies_count')
                    ->label(__('mksine::comments.replies'))
                    ->counts('replies')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('mksine::comments.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('mksine::comments.status'))
                    ->options([
                        Comment::STATUS_PENDING => __('mksine::comments.status_pending'),
                        Comment::STATUS_APPROVED => __('mksine::comments.status_approved'),
                        Comment::STATUS_SPAM => __('mksine::comments.status_spam'),
                        Comment::STATUS_TRASH => __('mksine::comments.status_trash'),
                    ])
                    ->native(false),
                SelectFilter::make('rating')
                    ->label(__('mksine::comments.rating'))
                    ->options([
                        1 => __('mksine::comments.rating_1_star'),
                        2 => __('mksine::comments.rating_2_stars'),
                        3 => __('mksine::comments.rating_3_stars'),
                        4 => __('mksine::comments.rating_4_stars'),
                        5 => __('mksine::comments.rating_5_stars'),
                    ])
                    ->native(false),
                SelectFilter::make('commentable_post_id')
                    ->label(__('mksine::comments.post'))
                    ->options(fn (): array => Post::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->query(function (Builder $query, array $data): void {
                        $v = $data['value'] ?? null;
                        if (! filled($v)) {
                            return;
                        }
                        $query->where('commentable_type', Post::class)
                            ->where('commentable_id', $v);
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('mksine::comments.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_APPROVED)
                    ->action(function (Comment $record): void {
                        $record->update(['status' => Comment::STATUS_APPROVED]);
                        Notification::make()
                            ->title(__('mksine::comments.comment_approved'))
                            ->success()
                            ->send();
                    }),
                Action::make('view')
                    ->label(__('mksine::comments.view'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Comment $record): string => __('mksine::comments.comment_by', ['name' => $record->author_display_name]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('mksine::common.close'))
                    ->form(fn (Comment $record): array => [
                        Section::make(__('mksine::comments.comment_details'))
                            ->schema([
                                Placeholder::make('commentable_title')
                                    ->label(__('mksine::comments.commentable'))
                                    ->content($record->commentable?->title ?? '-'),
                                Placeholder::make('author')
                                    ->label(__('mksine::comments.author'))
                                    ->content($record->author_display_name . ($record->author_display_email ? ' (' . $record->author_display_email . ')' : '')),
                                Placeholder::make('date')
                                    ->label(__('mksine::comments.date'))
                                    ->content($record->created_at?->format('F j, Y g:i A')),
                                Placeholder::make('rating')
                                    ->label(__('mksine::comments.rating'))
                                    ->content($record->rating ? str_repeat('⭐', $record->rating) . ' (' . $record->rating . '/5)' : __('mksine::comments.no_rating')),
                                Placeholder::make('status')
                                    ->label(__('mksine::comments.status'))
                                    ->content(ucfirst($record->status)),
                                Placeholder::make('type')
                                    ->label(__('mksine::comments.type'))
                                    ->content($record->parent_id ? __('mksine::comments.reply_to_comment', ['id' => $record->parent_id]) : __('mksine::comments.root_comment_label')),
                            ])
                            ->columns(3),
                        Section::make(__('mksine::common.content'))
                            ->schema([
                                Placeholder::make('content')
                                    ->label('')
                                    ->content(fn (): \Illuminate\Contracts\Support\Htmlable => new \Illuminate\Support\HtmlString(
                                        '<div class="prose dark:prose-invert max-w-none text-sm">' . nl2br(e($record->content)) . '</div>'
                                    )),
                            ]),
                        Section::make(__('mksine::comments.technical_info'))
                            ->schema([
                                Placeholder::make('ip')
                                    ->label(__('mksine::comments.ip_address'))
                                    ->content($record->ip_address ?? '-'),
                                Placeholder::make('user_agent')
                                    ->label(__('mksine::comments.user_agent'))
                                    ->content(\Illuminate\Support\Str::limit($record->user_agent ?? '-', 100)),
                            ])
                            ->columns(2)
                            ->collapsed(),
                    ]),
                Action::make('reply')
                    ->label(__('mksine::comments.reply'))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->form([
                        Textarea::make('content')
                            ->label(__('mksine::comments.reply_content'))
                            ->required()
                            ->rows(4)
                            ->maxLength(5000)
                            ->placeholder(__('mksine::comments.reply_placeholder')),
                    ])
                    ->action(function (Comment $record, array $data): void {
                        Comment::create([
                            'commentable_type' => $record->commentable_type,
                            'commentable_id' => $record->commentable_id,
                            'user_id' => Auth::id(),
                            'parent_id' => $record->id,
                            'content' => $data['content'],
                            'status' => Comment::STATUS_APPROVED,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);
                        Notification::make()
                            ->title(__('mksine::comments.reply_added'))
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    Action::make('spam')
                        ->label(__('mksine::comments.mark_as_spam'))
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_SPAM)
                        ->requiresConfirmation()
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_SPAM]);
                            Notification::make()
                                ->title(__('mksine::comments.marked_as_spam'))
                                ->warning()
                                ->send();
                        }),
                    Action::make('trash')
                        ->label(__('mksine::comments.move_to_trash'))
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_TRASH)
                        ->requiresConfirmation()
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_TRASH]);
                            Notification::make()
                                ->title(__('mksine::comments.moved_to_trash'))
                                ->warning()
                                ->send();
                        }),
                    Action::make('restore')
                        ->label(__('mksine::comments.restore_to_pending'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (Comment $record): bool => in_array($record->status, [Comment::STATUS_SPAM, Comment::STATUS_TRASH]))
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_PENDING]);
                            Notification::make()
                                ->title(__('mksine::comments.restored_to_pending'))
                                ->success()
                                ->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip(__('mksine::common.more_actions')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label(__('mksine::comments.approve_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_APPROVED]);
                            Notification::make()
                                ->title(__('mksine::comments.comments_approved', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('spam')
                        ->label(__('mksine::comments.mark_as_spam'))
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_SPAM]);
                            Notification::make()
                                ->title(__('mksine::comments.comments_marked_spam', ['count' => $records->count()]))
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('trash')
                        ->label(__('mksine::comments.move_to_trash'))
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_TRASH]);
                            Notification::make()
                                ->title(__('mksine::comments.comments_moved_trash', ['count' => $records->count()]))
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        // Apply table hooks
        $tableHookManager = app(TableHookManager::class);

        return $tableHookManager->apply('comment.table', $table);
    }
}
