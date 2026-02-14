<?php

namespace Miran\Mksine\Filament\Resources\Comments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Miran\Mksine\Filament\Resources\Comments\CommentResource;

class CreateComment extends CreateRecord
{
    protected static string $resource = CommentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set IP address and user agent for new comments
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();

        return $data;
    }
}
