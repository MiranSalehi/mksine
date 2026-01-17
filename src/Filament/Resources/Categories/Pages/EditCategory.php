<?php

namespace Miran\Mksine\Filament\Resources\Categories\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Events\Categories\CategoryUpdated;
use Miran\Mksine\Core\Events\Categories\CategoryUpdating;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
        ];

        // Apply page hooks
        $pageHookManager = app(PageHookManager::class);

        return $pageHookManager->applyHeaderActions('category.edit', $actions);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-generate slug from name if not provided
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }

        // Dispatch CategoryUpdating event
        $hookManager = app(HookManager::class);
        $event = new CategoryUpdating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
            'category_id' => $this->record->getKey(),
            'original_data' => $this->record->toArray(),
        ]);

        $result = $hookManager->dispatch($event);

        // If event was prevented, throw exception
        if ($result->wasPrevented()) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('category', $result->preventReason() ?? 'Category update was prevented.');

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Merge mutations from event back into data
        $mutatedData = $event->allData();

        return array_merge($data, $mutatedData);
    }

    protected function afterSave(): void
    {
        // Dispatch CategoryUpdated event
        $hookManager = app(HookManager::class);
        $event = new CategoryUpdated(
            $this->record->fresh()->toArray(),
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'category_id' => $this->record->getKey(),
            ]
        );

        $hookManager->dispatch($event);
    }
}
