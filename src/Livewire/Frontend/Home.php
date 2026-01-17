<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;

class Home extends Component
{
    #[Layout('mksine::components.layouts.app')]
    public function render()
    {
        return view('mksine::livewire.frontend.home', [
            'posts' => Post::query()
                ->where('status', 'published')
                ->latest('published_at')
                ->take(6)
                ->get(),
        ]);
    }
}
