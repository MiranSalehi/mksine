<?php

namespace Miran\Mksine\Filament\Resources\Tags\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Miran\Mksine\Core\Events\Tags\TagUpdated;
use Miran\Mksine\Core\Events\Tags\TagUpdating;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Filament\Resources\Tags\TagResource;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
        ];

        $pageHookManager = app(PageHookManager::class);

        return $pageHookManager->applyHeaderActions('tag.edit', $actions);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $hookManager = app(HookManager::class);
        $event = new TagUpdating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
            'tag_id' => $this->record->getKey(),
            'original_data' => $this->record->toArray(),
        ]);

        $result = $hookManager->dispatch($event);

        if ($result->wasPrevented()) {
            $validator = Validator::make([], []);
            $validator->errors()->add('tag', $result->preventReason() ?? 'Tag update was prevented.');

            throw new ValidationException($validator);
        }

        $mutatedData = $event->allData();

        return array_merge($data, $mutatedData);
    }

    protected function afterSave(): void
    {
        $hookManager = app(HookManager::class);
        $event = new TagUpdated(
            $this->record->fresh()->toArray(),
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'tag_id' => $this->record->getKey(),
            ]
        );

        $hookManager->dispatch($event);
    }
}
