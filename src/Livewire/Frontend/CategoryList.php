<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Category;

class CategoryList extends Component
{
    #[Layout('mksine::themes.mksine.layouts.index')]
    public function render()
    {
        View::share('title', __('Categories') . ' - ' . (config('app.name', 'MKS CMS')));

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true)->with('parent')->withCount(['posts' => fn ($q) => $q->where('status', 'published')]),
            ])
            ->withCount(['posts' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->get();

        return view('mksine::themes.mksine.categories', [
            'categories' => $categories,
        ]);
    }
}
