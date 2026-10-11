<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Miran\Mksine\Filament\Resources\PageBuilderSections\PageBuilderSectionResource;

class ListPageBuilderSections extends ListRecords
{
    protected static string $resource = PageBuilderSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
