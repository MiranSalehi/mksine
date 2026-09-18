<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Models\Post;

class AuthorShow extends Component
{
    use Concerns\EmitsStorefrontView;

    public bool $skipLayout = false;

    public $author;

    public function mount($id)
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);
        $this->author = $userClass::findOrFail($id);
    }

    public function render()
    {
        View::share('title', $this->author->name . ' - ' . __('mksine::frontend.author'));

        $posts = ContentVisibility::constrain(Post::query(), 'post')
            ->where('author_id', $this->author->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(12);

        $this->emitStorefrontView('author', $this->author->id);

        $view = view(theme_view('author'), ['posts' => $posts]);

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
