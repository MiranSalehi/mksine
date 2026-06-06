<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Resources\GeoStates\GeoStateResource;

class EditGeoState extends EditRecord
{
    protected static string $resource = GeoStateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->isDeletable()),
        ];
    }
}
