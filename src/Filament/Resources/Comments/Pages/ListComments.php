<?php

namespace Miran\Mksine\Filament\Resources\Comments\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Miran\Mksine\Filament\Resources\Comments\CommentResource;
use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Models\Comment;

class ListComments extends MksineListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActionsHookName(): ?string
    {
        return 'comment.list';
    }

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make(__('mksine::comments.tab_pending'))
                ->icon('heroicon-o-clock')
                ->badge(Comment::where('status', Comment::STATUS_PENDING)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_PENDING)),
            'approved' => Tab::make(__('mksine::comments.tab_approved'))
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_APPROVED)),
            'all' => Tab::make(__('mksine::comments.tab_all'))
                ->icon('heroicon-o-inbox'),
            'spam' => Tab::make(__('mksine::comments.tab_spam'))
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(Comment::where('status', Comment::STATUS_SPAM)->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_SPAM)),
            'trash' => Tab::make(__('mksine::comments.tab_trash'))
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Comment::STATUS_TRASH)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
