<?php

namespace Miran\Mksine\Filament\Resources\Posts\Pages;

use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Filament\Resources\Posts\PostResource;

class ListPosts extends MksineListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActionsHookName(): ?string
    {
        return 'post.list';
    }
}
