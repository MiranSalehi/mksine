<?php

namespace Miran\Mksine\Filament\Resources\Media\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Miran\Mksine\Filament\Resources\Media\MediaResource;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
