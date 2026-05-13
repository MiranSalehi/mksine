<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Models\Page;

class PageShow extends Component
{
    public bool $skipLayout = false;

    /** Set when opening via /page/{slug}. */
    public ?string $slug = null;

    /** Set when rendering front page from settings (no slug in URL). */
    public ?int $pageId = null;

    /** Not public so Livewire does not bind route param "page" (string) to it. */
    protected ?Page $pageModel = null;

    /**
     * Load page by pageId (front page from settings) or by slug (normal URL /page/{slug}).
     */
    public function mount(): void
    {
        if ($this->pageId !== null && $this->pageId > 0) {
            $this->pageModel = Page::where('id', $this->pageId)->published()->firstOrFail();
        } elseif ($this->slug !== null && $this->slug !== '') {
            $this->pageModel = Page::where('slug', $this->slug)
                ->where('status', 'published')
                ->firstOrFail();
        } else {
            abort(404);
        }
    }

    public function render()
    {
        View::share('title', mksine_document_title($this->pageModel->meta_title, $this->pageModel->title));
        View::share('metaDescription', mksine_meta_description($this->pageModel->meta_description, $this->pageModel->content));

        $view = view(theme_view('page'), ['page' => $this->pageModel]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
