<?php

namespace Miran\Mksine\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Miran\Mksine\Core\Events\Tags\TagCreated;
use Miran\Mksine\Core\Events\Tags\TagCreating;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Filament\Resources\Tags\TagResource;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $hookManager = app(HookManager::class);
        $event = new TagCreating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
        ]);

        $result = $hookManager->dispatch($event);

        if ($result->wasPrevented()) {
            $validator = Validator::make([], []);
            $validator->errors()->add('tag', $result->preventReason() ?? 'Tag creation was prevented.');

            throw new ValidationException($validator);
        }

        $mutatedData = $event->allData();

        return array_merge($data, $mutatedData);
    }

    protected function afterCreate(): void
    {
        $hookManager = app(HookManager::class);
        $event = new TagCreated(
            $this->record->toArray(),
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'tag_id' => $this->record->getKey(),
            ]
        );

        $hookManager->dispatch($event);
    }
}
