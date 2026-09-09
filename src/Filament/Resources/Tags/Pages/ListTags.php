<?php

namespace Miran\Mksine\Filament\Resources\Tags\Pages;

use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Filament\Resources\Tags\TagResource;

class ListTags extends MksineListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActionsHookName(): ?string
    {
        return 'tag.list';
    }
}
