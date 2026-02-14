<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Post;

class AuthorShow extends Component
{
    public $author;

    public function mount($id)
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);
        $this->author = $userClass::findOrFail($id);
    }

    #[Layout('mksine::themes.mksine.layouts.index')]
    public function render()
    {
        View::share('title', $this->author->name . ' - ' . __('Author'));

        $posts = Post::query()
            ->where('author_id', $this->author->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(12);

        return view('mksine::themes.mksine.author', [
            'posts' => $posts,
        ]);
    }
}
