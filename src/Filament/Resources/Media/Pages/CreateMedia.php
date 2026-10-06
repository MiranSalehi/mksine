<?php

namespace Miran\Mksine\Filament\Resources\Media\Pages;

use Filament\Resources\Pages\CreateRecord;
use Miran\Mksine\Filament\Resources\Media\MediaResource;
use Miran\Mksine\Support\MediaStoredFile;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $originalFileName = $data['file_name'] ?? null;
        $data = MediaStoredFile::forgetDerived($data);
        $data = MediaStoredFile::apply($data, originalFileName: $originalFileName);
        unset($data['file']);

        return $data;
    }
}
