<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;

class PostShow extends Component
{
    public Post $post;

    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['categories' => fn ($q) => $q->with('parent.parent.parent')])
            ->firstOrFail();
    }

    #[Layout('mksine::themes.mksine.layouts.index')]
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

        return view('mksine::themes.mksine.single', [
            'post' => $this->post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
