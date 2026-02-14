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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Hooks\TableHookManager;
use Miran\Mksine\Models\Comment;

class CommentTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('post.title')
                    ->label('Post')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Comment $record): string => $record->post?->title ?? ''),
                TextColumn::make('author_display_name')
                    ->label('Author')
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
                    ->label('Content')
                    ->searchable()
                    ->limit(80)
                    ->wrap()
                    ->html()
                    ->formatStateUsing(fn (Comment $record): string => nl2br(e(\Illuminate\Support\Str::limit($record->content, 80)))),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : '-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Comment::STATUS_APPROVED => 'success',
                        Comment::STATUS_PENDING => 'warning',
                        Comment::STATUS_SPAM => 'danger',
                        Comment::STATUS_TRASH => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('parent_id')
                    ->label('Type')
                    ->formatStateUsing(fn (?int $state): string => $state ? 'Reply' : 'Comment')
                    ->badge()
                    ->color(fn (?int $state): string => $state ? 'info' : 'primary'),
                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->counts('replies')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Comment::STATUS_PENDING => 'Pending',
                        Comment::STATUS_APPROVED => 'Approved',
                        Comment::STATUS_SPAM => 'Spam',
                        Comment::STATUS_TRASH => 'Trash',
                    ])
                    ->native(false),
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '1 Star',
                        2 => '2 Stars',
                        3 => '3 Stars',
                        4 => '4 Stars',
                        5 => '5 Stars',
                    ])
                    ->native(false),
                SelectFilter::make('post_id')
                    ->label('Post')
                    ->relationship('post', 'title')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_APPROVED)
                    ->action(function (Comment $record): void {
                        $record->update(['status' => Comment::STATUS_APPROVED]);
                        Notification::make()
                            ->title('Comment Approved')
                            ->success()
                            ->send();
                    }),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Comment $record): string => 'Comment by ' . $record->author_display_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(fn (Comment $record): array => [
                        Section::make('Comment Details')
                            ->schema([
                                Placeholder::make('post')
                                    ->label('Post')
                                    ->content($record->post?->title ?? '-'),
                                Placeholder::make('author')
                                    ->label('Author')
                                    ->content($record->author_display_name . ($record->author_display_email ? ' (' . $record->author_display_email . ')' : '')),
                                Placeholder::make('date')
                                    ->label('Date')
                                    ->content($record->created_at?->format('F j, Y g:i A')),
                                Placeholder::make('rating')
                                    ->label('Rating')
                                    ->content($record->rating ? str_repeat('⭐', $record->rating) . ' (' . $record->rating . '/5)' : 'No rating'),
                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content(ucfirst($record->status)),
                                Placeholder::make('type')
                                    ->label('Type')
                                    ->content($record->parent_id ? 'Reply to comment #' . $record->parent_id : 'Root comment'),
                            ])
                            ->columns(3),
                        Section::make('Content')
                            ->schema([
                                Placeholder::make('content')
                                    ->label('')
                                    ->content(fn (): \Illuminate\Contracts\Support\Htmlable => new \Illuminate\Support\HtmlString(
                                        '<div class="prose dark:prose-invert max-w-none text-sm">' . nl2br(e($record->content)) . '</div>'
                                    )),
                            ]),
                        Section::make('Technical Info')
                            ->schema([
                                Placeholder::make('ip')
                                    ->label('IP Address')
                                    ->content($record->ip_address ?? '-'),
                                Placeholder::make('user_agent')
                                    ->label('User Agent')
                                    ->content(\Illuminate\Support\Str::limit($record->user_agent ?? '-', 100)),
                            ])
                            ->columns(2)
                            ->collapsed(),
                    ]),
                Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->form([
                        Textarea::make('content')
                            ->label('Reply Content')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000)
                            ->placeholder('Write your reply...'),
                    ])
                    ->action(function (Comment $record, array $data): void {
                        Comment::create([
                            'post_id' => $record->post_id,
                            'user_id' => Auth::id(),
                            'parent_id' => $record->id,
                            'content' => $data['content'],
                            'status' => Comment::STATUS_APPROVED,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);
                        Notification::make()
                            ->title('Reply Added')
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    Action::make('spam')
                        ->label('Mark as Spam')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_SPAM)
                        ->requiresConfirmation()
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_SPAM]);
                            Notification::make()
                                ->title('Marked as Spam')
                                ->warning()
                                ->send();
                        }),
                    Action::make('trash')
                        ->label('Move to Trash')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->visible(fn (Comment $record): bool => $record->status !== Comment::STATUS_TRASH)
                        ->requiresConfirmation()
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_TRASH]);
                            Notification::make()
                                ->title('Moved to Trash')
                                ->warning()
                                ->send();
                        }),
                    Action::make('restore')
                        ->label('Restore to Pending')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (Comment $record): bool => in_array($record->status, [Comment::STATUS_SPAM, Comment::STATUS_TRASH]))
                        ->action(function (Comment $record): void {
                            $record->update(['status' => Comment::STATUS_PENDING]);
                            Notification::make()
                                ->title('Restored to Pending')
                                ->success()
                                ->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('More Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_APPROVED]);
                            Notification::make()
                                ->title($records->count() . ' comments approved')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('spam')
                        ->label('Mark as Spam')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_SPAM]);
                            Notification::make()
                                ->title($records->count() . ' comments marked as spam')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('trash')
                        ->label('Move to Trash')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => Comment::STATUS_TRASH]);
                            Notification::make()
                                ->title($records->count() . ' comments moved to trash')
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
