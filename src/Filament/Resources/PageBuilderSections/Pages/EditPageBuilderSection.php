<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections\Pages;

use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Resources\PageBuilderSections\PageBuilderSectionResource;

class EditPageBuilderSection extends EditRecord
{
    protected static string $resource = PageBuilderSectionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['builder_payload'] = is_array($data['builder_payload'] ?? null) ? $data['builder_payload'] : [];

        return $data;
    }
}
