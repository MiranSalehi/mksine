<?php

namespace Miran\Mksine\Filament\Resources\Media\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Resources\Media\MediaResource;
use Miran\Mksine\Support\MediaStoredFile;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['path']) && $data['path'] !== '') {
            $data['file'] = $data['path'];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $originalFileName = $data['file_name'] ?? null;
        $existingPath = is_string($this->record?->path) ? $this->record->path : null;

        $data = MediaStoredFile::forgetDerived($data);
        $data = MediaStoredFile::apply(
            $data,
            existingPath: $existingPath,
            originalFileName: $originalFileName,
        );
        unset($data['file']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        $this->form->fill([
            'file' => $this->record->path,
        ]);
    }
}
