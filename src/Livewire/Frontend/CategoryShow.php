<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Models\Category;

class CategoryShow extends Component
{
    use Concerns\EmitsStorefrontView;

    public bool $skipLayout = false;

    public Category $category;

    public function mount($path)
    {
        $path = is_string($path) ? trim($path, '/') : '';
        $category = Category::findByFullPath($path);

        if (! $category || ! $category->is_active) {
            abort(404);
        }

        $this->category = $category;
        $this->category->loadMissing(['parent.parent.parent.parent.parent']);
    }

    public function render()
    {
        View::share('title', mksine_document_title($this->category->meta_title, $this->category->name));
        View::share('metaDescription', mksine_meta_description($this->category->meta_description, $this->category->description));
        View::share('mksShortcodeContext', mks_shortcode_context(category: $this->category));

        $posts = ContentVisibility::constrain($this->category->posts()->getQuery(), 'post')
            ->published()
            ->with(['author', 'featuredImage', 'categories.parent.parent.parent'])
            ->latest('posts.published_at')
            ->paginate(12);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        $this->emitStorefrontView('category', $this->category->id, $this->category->is_active ? 'published' : 'inactive');

        $view = view(theme_view('category'), [
            'posts' => $posts,
            'categories' => $categories,
            'breadcrumbPath' => $this->category->getBreadcrumbPath(),
        ]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
