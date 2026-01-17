<?php

namespace Miran\Mksine\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use Miran\Mksine\Filament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
