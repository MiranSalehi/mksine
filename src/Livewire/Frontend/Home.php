<?php

namespace Miran\Mksine\Livewire\Frontend;

use Livewire\Component;

class Home extends Component
{
    use Concerns\EmitsStorefrontView;

    /** When true, component is embedded in FrontendResolver; do not apply layout. */
    public bool $skipLayout = false;

    public function render()
    {
        $this->emitStorefrontView('home');

        $view = view(theme_view('home'));

        return $this->skipLayout ? $view : $view->layout(theme_layout());
    }
}
