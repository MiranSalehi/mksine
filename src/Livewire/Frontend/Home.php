<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Category;

class Home extends Component
{
    #[Layout('mksine::themes.mksine.layouts.index')]
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


        return view('mksine::themes.mksine.home', [
            'latestPosts' => $latestPosts,
            'categories' => $categories,
        ]);
    }
}
