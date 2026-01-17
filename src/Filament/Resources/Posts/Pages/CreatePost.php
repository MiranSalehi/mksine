<?php

namespace Miran\Mksine\Filament\Resources\Posts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Events\Posts\PostCreated;
use Miran\Mksine\Core\Events\Posts\PostCreating;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Filament\Resources\Posts\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set author if not set
        if (! isset($data['author_id']) && Auth::check()) {
            $data['author_id'] = Auth::id();
        }

        // Auto-generate slug from title if not provided
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['title']);
        }

        // Dispatch PostCreating event
        $hookManager = app(HookManager::class);
        $event = new PostCreating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
        ]);

        $result = $hookManager->dispatch($event);

        // If event was prevented, throw exception
        if ($result->wasPrevented()) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('post', $result->preventReason() ?? 'Post creation was prevented.');

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Merge mutations from event back into data
        $mutatedData = $event->allData();

        return array_merge($data, $mutatedData);
    }

    protected function afterCreate(): void
    {
        // Dispatch PostCreated event
        $hookManager = app(HookManager::class);
        $event = new PostCreated(
            $this->record->toArray(),
            [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'post_id' => $this->record->getKey(),
            ]
        );

        $hookManager->dispatch($event);
    }
}
