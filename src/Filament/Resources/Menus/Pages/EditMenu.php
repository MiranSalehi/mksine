<?php

namespace Miran\Mksine\Filament\Resources\Menus\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Pages\MenuBuilder;
use Miran\Mksine\Filament\Resources\Menus\MenuResource;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('builder')
                ->label(__('Edit Menu Items'))
                ->icon('heroicon-o-bars-3-bottom-left')
                ->url(fn () => MenuBuilder::getUrl(['menu' => $this->record->id])),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
