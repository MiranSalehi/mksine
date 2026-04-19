<?php

namespace Miran\Mksine\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\EditRecord;
use Miran\Mksine\Filament\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
