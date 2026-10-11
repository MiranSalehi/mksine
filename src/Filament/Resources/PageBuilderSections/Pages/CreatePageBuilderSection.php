<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Filament\Resources\PageBuilderSections\PageBuilderSectionResource;

class CreatePageBuilderSection extends CreateRecord
{
    protected static string $resource = PageBuilderSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['builder_payload'] = is_array($data['builder_payload'] ?? null) ? $data['builder_payload'] : [];

        return $data;
    }
}
