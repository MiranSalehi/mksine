<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Category;

class CategoryList extends Component
{
    #[Layout('mksine::components.layouts.app')]
    public function render()
    {
        return view('mksine::livewire.frontend.category-list', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->with('children')
                ->get(),
        ]);
    }
}
