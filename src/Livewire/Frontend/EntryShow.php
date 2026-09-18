<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Core\Content\ContentType;
use Miran\Mksine\Core\Content\ContentTypeRegistry;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Models\Entry;

class EntryShow extends Component
{
    use Concerns\EmitsStorefrontView;
    use Concerns\ResolvesThemeView;

    public bool $skipLayout = false;

    public string $contentType = '';

    public string $slug = '';

    public Entry $entry;

    public function mount(string $slug, string $contentType): void
    {
        $this->contentType = $contentType;
        $this->slug = $slug;

        $this->entry = Entry::query()
            ->ofType($contentType)
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        ContentVisibility::assertVisible($this->entry, $contentType);
    }

    public function render()
    {
        $this->entry->loadMissing(['author', 'featuredImage', 'tags']);

        $type = $this->type();

        View::share('title', mksine_document_title($this->entry->meta_title, $this->entry->title));
        $fallbackDesc = trim((string) ($this->entry->excerpt ?? '')) !== ''
            ? $this->entry->excerpt
            : (string) ($this->entry->content ?? '');
        View::share('metaDescription', mksine_meta_description($this->entry->meta_description, $fallbackDesc));

        $this->emitStorefrontView($this->contentType, $this->entry->id, $this->entry->status);

        $view = view($this->resolveThemeView('entry-'.$this->contentType, 'entry', 'mksine::themes.mksine.entry'), [
            'entry' => $this->entry,
            'type' => $type,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }

    public function type(): ContentType
    {
        return app(ContentTypeRegistry::class)->get($this->contentType);
    }
}
