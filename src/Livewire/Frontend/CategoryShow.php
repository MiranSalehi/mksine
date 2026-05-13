<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Models\Category;

class CategoryShow extends Component
{
    public bool $skipLayout = false;

    public Category $category;

    public function mount($path)
    {
        $path = is_string($path) ? trim($path, '/') : '';
        $this->category = Category::findByFullPath($path);
        if (! $this->category) {
            abort(404);
        }

        $this->category->loadMissing(['parent.parent.parent.parent.parent']);
    }

    public function render()
    {
        View::share('title', mksine_document_title($this->category->meta_title, $this->category->name));
        View::share('metaDescription', mksine_meta_description($this->category->meta_description, $this->category->description));

        $posts = $this->category->posts()
            ->where('posts.status', 'published')
            ->with(['author', 'featuredImage', 'categories'])
            ->latest('posts.published_at')
            ->paginate(12);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        $view = view(theme_view('category'), [
            'posts' => $posts,
            'categories' => $categories,
            'breadcrumbPath' => $this->category->getBreadcrumbPath(),
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
