<?php

namespace Miran\Mksine\Filament\Resources\MenuLocations\Pages;

use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;
use Miran\Mksine\Core\Hooks\MenuLocationManager;
use Miran\Mksine\Filament\Resources\MenuLocations\MenuLocationResource;

class ListMenuLocations extends MksineListRecords
{
    protected static string $resource = MenuLocationResource::class;

    public function mount(): void
    {
        // Sync registered locations to database
        app(MenuLocationManager::class)->syncToDatabase();

        parent::mount();
    }
}
