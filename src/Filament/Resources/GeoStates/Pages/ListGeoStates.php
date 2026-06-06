<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates\Pages;

use Filament\Actions\CreateAction;
use Miran\Mksine\Filament\Resources\GeoStates\GeoStateResource;
use Miran\Mksine\Filament\Resources\GeoStates\Schemas\GeoStateForm;
use Miran\Mksine\Filament\Resources\Pages\MksineListRecords;

class ListGeoStates extends MksineListRecords
{
    protected static string $resource = GeoStateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => GeoStateForm::mutateCreateData($data)),
        ];
    }
}
