<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Post;

class PostList extends Component
{
    use Concerns\EmitsStorefrontView;
    use WithPagination;

    public bool $skipLayout = false;

    public function render()
    {
        $posts = ContentVisibility::constrain(Post::query(), 'post')
            ->published()
            ->with(['author', 'featuredImage', 'categories.parent.parent.parent'])
            ->latest('published_at')
            ->paginate(12);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->take(10)
            ->get();

        $this->emitStorefrontView('post_list');

        $view = view(theme_view('posts'), ['posts' => $posts, 'categories' => $categories]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
