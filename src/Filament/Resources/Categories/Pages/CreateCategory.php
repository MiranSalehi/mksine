<?php

namespace Miran\Mksine\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Events\Categories\CategoryCreated;
use Miran\Mksine\Core\Events\Categories\CategoryCreating;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate slug from name if not provided
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        // Dispatch CategoryCreating event
        $hookManager = app(HookManager::class);
        $event = new CategoryCreating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
        ]);

        $result = $hookManager->dispatch($event);

        // If event was prevented, throw exception
        if ($result->wasPrevented()) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('category', $result->preventReason() ?? 'Category creation was prevented.');

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Merge mutations from event back into data
        $mutatedData = $event->allData();

        return array_merge($data, $mutatedData);
    }

    protected function afterCreate(): void
    {
        // Dispatch CategoryCreated event
        $hookManager = app(HookManager::class);
        $event = new CategoryCreated(
            $this->record->toArray(),
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'category_id' => $this->record->getKey(),
            ]
        );

        $hookManager->dispatch($event);
    }
}
