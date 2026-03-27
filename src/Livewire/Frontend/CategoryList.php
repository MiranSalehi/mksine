<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Models\Category;

class CategoryList extends Component
{
    public bool $skipLayout = false;

    public function render()
    {
        View::share('title', __('mksine::frontend.categories') . ' - ' . (config('app.name', 'MKS CMS')));

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true)->with('parent')->withCount(['posts' => fn ($q) => $q->where('status', 'published')]),
            ])
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->get();

        $view = view(theme_view('categories'), ['categories' => $categories]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
