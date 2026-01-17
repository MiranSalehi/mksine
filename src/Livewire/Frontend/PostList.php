<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;

class PostList extends Component
{
    use WithPagination;

    #[Layout('mksine::components.layouts.app')]
    public function render()
    {
        return view('mksine::livewire.frontend.post-list', [
            'posts' => Post::query()
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(12),
        ]);
    }
}
