<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Category;

class CategoryShow extends Component
{
    public Category $category;

    public function mount($slug)
    {
        $this->category = Category::where('slug', $slug)->firstOrFail();
    }

    #[Layout('mksine::components.layouts.app')]
    public function render()
    {
        return view('mksine::livewire.frontend.category-show', [
            'posts' => $this->category->posts()
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(12),
        ]);
    }
}
