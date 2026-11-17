<?php

declare(strict_types=1);

namespace Miran\Mksine\Tests\Fixtures\Frontend;

use Livewire\Component;

/**
 * A theme's Livewire page override, as registered with ThemeRegistry::registerOverride('home', …).
 */
class ThemeHomeOverride extends Component
{
    public bool $skipLayout = false;

    public function render(): string
    {
        return '<div>theme livewire home</div>';
    }
}
