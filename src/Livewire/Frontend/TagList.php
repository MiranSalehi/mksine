<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Models\Tag;

class TagList extends Component
{
    public bool $skipLayout = false;

    public function render()
    {
        View::share('title', __('mksine::frontend.tags').' - '.(config('app.name', 'MKS CMS')));

        $tags = Tag::query()
            ->where('is_active', true)
            ->withCount([
                'posts' => fn ($q) => $q->where('status', 'published'),
                'pages' => fn ($q) => $q->published(),
            ])
            ->orderBy('name')
            ->get();

        $view = view(theme_view('tags'), ['tags' => $tags]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
