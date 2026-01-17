<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;

class PostShow extends Component
{
    public Post $post;

    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    #[Layout('mksine::components.layouts.app')]
    public function render()
    {
        return view('mksine::livewire.frontend.post-show', [
            'post' => $this->post,
        ]);
    }
}
