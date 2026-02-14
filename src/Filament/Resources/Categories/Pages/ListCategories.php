<?php

namespace Miran\Mksine\Filament\Resources\Categories\Pages;

use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;

class ListCategories extends MksineListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActionsHookName(): ?string
    {
        return 'category.list';
    }
}
