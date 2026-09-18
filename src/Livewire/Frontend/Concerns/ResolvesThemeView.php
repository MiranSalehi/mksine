<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire\Frontend\Concerns;

trait ResolvesThemeView
{
    protected function resolveThemeView(string $specific, string $generic, string $packageFallback): string
    {
        foreach ([$specific, $generic] as $name) {
            $full = theme_view($name);
            if (view()->exists($full)) {
                return $full;
            }
        }

        return $packageFallback;
    }
}
