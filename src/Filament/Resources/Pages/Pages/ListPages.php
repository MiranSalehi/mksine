<?php

namespace Miran\Mksine\Filament\Resources\Pages\Pages;

use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Filament\Resources\Pages\PageResource;

class ListPages extends MksineListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActionsHookName(): ?string
    {
        return 'page.list';
    }
}
