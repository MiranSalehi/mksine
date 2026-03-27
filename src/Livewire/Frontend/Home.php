<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Category;

class Home extends Component
{
    /** When true, component is embedded in FrontendResolver; do not apply layout. */
    public bool $skipLayout = false;

    public function render()
    {
        $latestPosts = Post::query()
            ->where('status', 'published')
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::query()
            ->with('parent')
            ->take(8)
            ->latest()
            ->get();

        $view = view(theme_view('home'), [
            'latestPosts' => $latestPosts,
            'categories' => $categories,
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
