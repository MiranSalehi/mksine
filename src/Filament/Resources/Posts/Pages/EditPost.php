<?php

namespace Miran\Mksine\Filament\Resources\Posts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Content\PostLifecycle;
use Miran\Mksine\Core\Events\Posts\PostUpdating;
use Miran\Mksine\Core\Hooks\ContentFormHooks;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Filament\Resources\Posts\PostResource;
use Miran\Mksine\Models\Post;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    private ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
        ];

        // Apply page hooks
        $pageHookManager = app(PageHookManager::class);

        return $pageHookManager->applyHeaderActions('post.edit', $actions);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-generate slug from title if not provided
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['title']);
        }

        $this->statusBeforeSave = is_string($this->record->status) ? $this->record->status : null;

        // Dispatch PostUpdating event
        $hookManager = app(HookManager::class);
        $event = new PostUpdating($data, [
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip' => request()->ip(),
            'post_id' => $this->record->getKey(),
            'original_data' => $this->record->toArray(),
        ]);

        $result = $hookManager->dispatch($event);

        // If event was prevented, throw exception
        if ($result->wasPrevented()) {
            $validator = \Illuminate\Support\Facades\Validator::make([], []);
            $validator->errors()->add('post', $result->preventReason() ?? 'Post update was prevented.');

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Merge mutations from event back into data
        $mutatedData = $event->allData();

        return ContentFormHooks::mutate(array_merge($data, $mutatedData), 'post', $this->record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ContentFormHooks::fill($data, 'post', $this->record);
    }

    protected function afterSave(): void
    {
        $post = $this->record->fresh();
        if ($post instanceof Post) {
            PostLifecycle::dispatchUpdated($post, [
                'user_id' => Auth::check() ? Auth::id() : null,
                'ip' => request()->ip(),
                'post_id' => $this->record->getKey(),
            ], $this->statusBeforeSave);
        }

        ContentFormHooks::saved($this->record, 'post');
    }
}
