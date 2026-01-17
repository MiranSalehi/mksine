<?php

namespace Miran\Mksine\Filament\Resources\MenuLocations\Pages;

use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Resources\MenuLocations\MenuLocationResource;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuLocationAssignment;

class EditMenuLocation extends EditRecord
{
    protected static string $resource = MenuLocationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the currently assigned menu
        $assignment = MenuLocationAssignment::where('menu_location_id', $this->record->id)->first();
        $data['menu_id'] = $assignment?->menu_id;

        return $data;
    }

    protected function afterSave(): void
    {
        $menuId = $this->data['menu_id'] ?? null;

        // Remove existing assignment
        MenuLocationAssignment::where('menu_location_id', $this->record->id)->delete();

        // Create new assignment if menu selected
        if ($menuId) {
            MenuLocationAssignment::create([
                'menu_id' => $menuId,
                'menu_location_id' => $this->record->id,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
