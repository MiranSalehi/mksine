<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Post;

class PostShow extends Component
{
    public bool $skipLayout = false;

    public Post $post;

    public function mount($slug): void
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['categories' => fn ($q) => $q->with('parent.parent.parent')])
            ->firstOrFail();
    }

    public function render()
    {
        $this->post->loadMissing([
            'author',
            'featuredImage',
            'categories' => fn ($q) => $q->with('parent.parent.parent'),
        ]);

        $categoryIds = $this->post->categories->pluck('id')->toArray();

        $relatedPosts = Post::query()
            ->where('status', 'published')
            ->where('id', '!=', $this->post->id)
            ->when(count($categoryIds) > 0, fn ($q) => $q->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)))
            ->with(['featuredImage', 'author', 'categories'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $recentPosts = Post::query()
            ->where('status', 'published')
            ->where('id', '!=', $this->post->id)
            ->with(['featuredImage', 'author'])
            ->latest('published_at')
            ->take(5)
            ->get();

        $sidebarCategories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->whereHas('posts', fn ($q) => $q->where('status', 'published'))
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(12)
            ->get();

        View::share('title', mksine_document_title($this->post->meta_title, $this->post->title));
        $fallbackDesc = trim((string) ($this->post->excerpt ?? '')) !== ''
            ? $this->post->excerpt
            : (string) ($this->post->content ?? '');
        View::share('metaDescription', mksine_meta_description($this->post->meta_description, $fallbackDesc));
        View::share('mksShortcodeContext', mks_shortcode_context(post: $this->post));

        $view = view(theme_view('single'), [
            'post' => $this->post,
            'relatedPosts' => $relatedPosts,
            'recentPosts' => $recentPosts,
            'sidebarCategories' => $sidebarCategories,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
