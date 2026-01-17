<?php

namespace Miran\Mksine\Filament\Resources\Menus\Pages;

use Filament\Resources\Pages\CreateRecord;
use Miran\Mksine\Filament\Resources\Menus\MenuResource;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
