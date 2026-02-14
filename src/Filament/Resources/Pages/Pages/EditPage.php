<?php

namespace Miran\Mksine\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Filament\Resources\Pages\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
