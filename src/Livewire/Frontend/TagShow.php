<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\WithPagination;
use Miran\Mksine\Models\Tag;

class TagShow extends Component
{
    use WithPagination;

    public bool $skipLayout = false;

    public Tag $tag;

    public function mount($slug): void
    {
        $slug = is_string($slug) ? trim($slug, '/') : '';
        $tag = $slug !== ''
            ? Tag::query()->where('slug', $slug)->where('is_active', true)->first()
            : null;

        if (! $tag) {
            abort(404);
        }

        $this->tag = $tag;
    }

    public function render()
    {
        View::share('title', mksine_document_title($this->tag->meta_title, $this->tag->name));
        View::share('metaDescription', mksine_meta_description($this->tag->meta_description, $this->tag->description));
        View::share('mksShortcodeContext', mks_shortcode_context());

        $posts = $this->tag->posts()
            ->where('posts.status', 'published')
            ->with(['author', 'featuredImage'])
            ->latest('posts.published_at')
            ->paginate(12, ['*'], 'posts');

        $pages = $this->tag->pages()
            ->published()
            ->latest('pages.published_at')
            ->paginate(12, ['*'], 'pages');

        $view = view(theme_view('tag'), [
            'posts' => $posts,
            'pages' => $pages,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
