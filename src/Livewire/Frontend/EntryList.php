<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\WithPagination;
use Miran\Mksine\Core\Content\ContentType;
use Miran\Mksine\Core\Content\ContentTypeRegistry;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Models\Entry;

class EntryList extends Component
{
    use Concerns\EmitsStorefrontView;
    use Concerns\ResolvesThemeView;
    use WithPagination;

    public bool $skipLayout = false;

    public string $contentType = '';

    public function mount(string $contentType): void
    {
        $this->contentType = $contentType;
        abort_unless(app(ContentTypeRegistry::class)->has($contentType), 404);
        abort_unless(app(ContentTypeRegistry::class)->get($contentType)->hasArchive, 404);
    }

    public function render()
    {
        $type = $this->type();

        $entries = ContentVisibility::constrain(Entry::query(), $this->contentType)
            ->ofType($this->contentType)
            ->published()
            ->with(['author', 'featuredImage'])
            ->latest('published_at')
            ->paginate(12);

        View::share('title', mksine_document_title(null, $type->pluralLabel));

        $this->emitStorefrontView($this->contentType.'_list');

        $view = view($this->resolveThemeView('entries-'.$this->contentType, 'entries', 'mksine::themes.mksine.entries'), [
            'entries' => $entries,
            'type' => $type,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }

    public function type(): ContentType
    {
        return app(ContentTypeRegistry::class)->get($this->contentType);
    }
}
