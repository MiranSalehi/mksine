<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Miran\Mksine\Models\Post;

class PostShow extends Component
{
    public bool $skipLayout = false;

    public Post $post;

    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['categories' => fn ($q) => $q->with('parent.parent.parent')])
            ->firstOrFail();
    }

    public function render()
    {
        $categoryIds = $this->post->categories->pluck('id')->toArray();

        $relatedPosts = Post::query()
            ->where('status', 'published')
            ->where('id', '!=', $this->post->id)
            ->when(count($categoryIds) > 0, fn ($q) => $q->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds)))
            ->with(['featuredImage', 'author', 'categories'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $view = view(theme_view('single'), [
            'post' => $this->post,
            'relatedPosts' => $relatedPosts,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
