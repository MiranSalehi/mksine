<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Category;

class CategoryShow extends Component
{
    public Category $category;

    public function mount($path)
    {
        $path = is_string($path) ? trim($path, '/') : '';
        $this->category = Category::findByFullPath($path);
        if (! $this->category) {
            abort(404);
        }
    }

    #[Layout('mksine::themes.mksine.layouts.index')]
    public function render()
    {
        View::share('title', $this->category->name . ' - ' . __('Category'));

        $posts = $this->category->posts()
            ->where('posts.status', 'published')
            ->latest('posts.published_at')
            ->paginate(12);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        return view('mksine::themes.mksine.category', [
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }
}
