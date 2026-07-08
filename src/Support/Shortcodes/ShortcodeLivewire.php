<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Shortcodes;

use Livewire\Livewire;

final class ShortcodeLivewire
{
    /**
     * @param  class-string  $componentClass
     * @param  array<string, mixed>  $params
     */
    public static function mount(string $componentClass, array $params = []): string
    {
        if (! class_exists(Livewire::class)) {
            return '';
        }

        return Livewire::mount($componentClass, $params)->html();
    }
}
