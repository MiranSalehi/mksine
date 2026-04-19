<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Theme;

use Livewire\Component;

/**
 * Maps Livewire dotted component names for theme classes (e.g. themes.voltech.livewire.profile-orders)
 * back to FQCNs under Themes\*\Livewire\*. Default Livewire resolution prepends App\Livewire instead.
 */
final class ThemeLivewireMissingComponentResolver
{
    public static function resolve(string $name): ?string
    {
        if (! str_starts_with($name, 'themes.')) {
            return null;
        }

        ThemeBootstrap::ensureActiveThemeAutoloadRegistered();

        $fqcn = '\\'.collect(explode('.', $name))
            ->map(fn (string $segment) => str($segment)->studly()->toString())
            ->implode('\\');

        if (! class_exists($fqcn)) {
            return null;
        }

        if (! is_subclass_of($fqcn, Component::class)) {
            return null;
        }

        return $fqcn;
    }
}
