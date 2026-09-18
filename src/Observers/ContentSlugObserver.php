<?php

declare(strict_types=1);

namespace Miran\Mksine\Observers;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Core\Events\Content\ContentSlugChanged;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Models\Entry;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Throwable;

class ContentSlugObserver
{
    public function updated(Model $model): void
    {
        if (! $model instanceof Post && ! $model instanceof Page && ! $model instanceof Entry) {
            return;
        }

        if (! $model->wasChanged('slug')) {
            return;
        }

        $oldSlug = (string) $model->getOriginal('slug');
        $newSlug = (string) $model->slug;

        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        $type = match (true) {
            $model instanceof Entry => (string) $model->content_type,
            $model instanceof Post => 'post',
            default => 'page',
        };

        $event = new ContentSlugChanged([
            'type' => $type,
            'id' => $model->getKey(),
            'old' => $oldSlug,
            'new' => $newSlug,
            'old_path' => Permalink::storefrontPathForContent($type, $oldSlug),
            'new_path' => Permalink::storefrontPathForContent($type, $newSlug),
        ]);

        try {
            app(HookManager::class)->dispatch($event);
            Hooks::filter(ContentSlugChanged::FILTER, $event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
