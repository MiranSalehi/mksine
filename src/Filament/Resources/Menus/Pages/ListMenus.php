<?php

namespace Miran\Mksine\Filament\Resources\Menus\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Miran\Mksine\Filament\Resources\Menus\MenuResource;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
