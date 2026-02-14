<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Miran\Mksine\Models\Page;

class PageShow extends Component
{
    public Page $page;

    public function mount($slug)
    {
        $this->page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
    }

    #[Layout('mksine::themes.mksine.layouts.index')]
    public function render()
    {
        View::share('title', $this->page->title . ' - ' . (config('app.name', 'MKS CMS')));

        return view('mksine::themes.mksine.page', [
            'page' => $this->page,
        ]);
    }
}
