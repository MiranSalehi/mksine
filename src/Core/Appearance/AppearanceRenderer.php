<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Turns the resolved look into what the browser needs: CSS colour variables, `data-*` attributes on
 * `<html>` and the light/dark mode, all decided on the server and written into `<head>` before the
 * body paints (no flash of the default look, also after `wire:navigate`).
 *
 * An empty look renders nothing at all, so the panel stays exactly as it was.
 */
final class AppearanceRenderer
{
    private ?Appearance $current = null;

    public function __construct(private readonly AppearanceRepository $repository) {}

    /**
     * The look of this request: company, then the signed-in person's own parts.
     */
    public function current(): Appearance
    {
        if ($this->current !== null) {
            return $this->current;
        }

        try {
            return $this->current = $this->repository->resolvedFor(Auth::user());
        } catch (Throwable) {
            return $this->current = Appearance::empty();
        }
    }

    /**
     * Forget the memoized look (after a save in the same request).
     */
    public function flush(): void
    {
        $this->current = null;
    }

    /**
     * Give Filament the palettes, so it picks readable text colours for buttons and badges.
     */
    public function registerColors(): void
    {
        $primary = $this->current()->primary;

        if ($primary === null) {
            return;
        }

        FilamentColor::register([
            'primary' => ColorScale::palette($primary)->shades,
            'gray' => ColorScale::grays($primary),
        ]);
    }

    /**
     * `<style>` + `<script>` for `<head>` (render hook after the panel styles); empty for the default look.
     */
    public function renderHead(): string
    {
        $look = $this->current();

        if ($look->isEmpty()) {
            return '';
        }

        $state = $this->clientState($look, withDefaults: false);
        $variables = $this->cssBlock($state['vars']);

        $style = $variables === '' ? '' : '<style data-mks-appearance>html.fi{' . $variables . '}</style>';

        return $style . '<script data-mks-appearance>' . $this->bootScript($state) . '</script>';
    }

    /**
     * What the editor applies to the page while previewing $look (every variable, defaults included,
     * so switching back to "default" also resets what the saved look had set).
     *
     * @return array{vars: array<string, string>, attrs: array<string, string>, mode: string|null, adjusted: bool, nearDanger: bool, used: string|null}
     */
    public function previewState(Appearance $look): array
    {
        return $this->clientState($look, withDefaults: true);
    }

    /**
     * @return array{vars: array<string, string>, attrs: array<string, string>, mode: string|null, adjusted: bool, nearDanger: bool, used: string|null}
     */
    private function clientState(Appearance $look, bool $withDefaults): array
    {
        $vars = [];
        $adjusted = false;
        $nearDanger = false;
        $used = null;

        if ($look->primary !== null) {
            $palette = ColorScale::palette($look->primary);
            $adjusted = $palette->adjusted;
            $nearDanger = ColorScale::isNearDanger($look->primary);
            $used = $palette->base();

            $vars += self::prefixed('primary', $palette->shades);
            $vars += self::prefixed('gray', ColorScale::grays($look->primary));
            $vars += self::prefixed('mks-accent', ColorScale::accent($look->primary));
        } elseif ($withDefaults) {
            $vars += self::prefixed('primary', Color::Blue);
            $vars += self::prefixed('gray', Color::Zinc);
        }

        $attrs = [];

        foreach (['rail', 'radius', 'font', 'density', 'field', 'label'] as $part) {
            $value = $look->{$part};

            if ($value !== null) {
                $attrs['data-' . $part] = (string) $value->value;
            }
        }

        if ($look->mode !== null) {
            $attrs['data-mode'] = $look->mode->value;
        }

        return [
            'vars' => $vars,
            'attrs' => $attrs,
            'mode' => $look->mode?->value,
            'adjusted' => $adjusted,
            'nearDanger' => $nearDanger,
            'used' => $used,
        ];
    }

    /**
     * @param  array<int|string, string>  $shades
     * @return array<string, string>
     */
    private static function prefixed(string $name, array $shades): array
    {
        $out = [];

        foreach ($shades as $step => $value) {
            $out["--{$name}-{$step}"] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function cssBlock(array $vars): string
    {
        $css = '';

        foreach ($vars as $name => $value) {
            // Values come from the colour engine (hex / oklch), never from user input directly.
            if (preg_match('/^--[a-z0-9-]+$/', $name) === 1 && preg_match('/^[#a-z0-9(). ]+$/i', $value) === 1) {
                $css .= $name . ':' . $value . ';';
            }
        }

        return $css;
    }

    /**
     * Sets the attributes and the stored theme before Filament's own dark-mode script runs, and again
     * after every `wire:navigate` swap. "System" follows the OS live.
     *
     * @param  array{attrs: array<string, string>, mode: string|null}  $state
     */
    private function bootScript(array $state): string
    {
        $json = json_encode(
            ['attrs' => $state['attrs'], 'mode' => $state['mode']],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );

        return <<<JS
(function () {
    var look = {$json};
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var apply = function () {
        Object.keys(look.attrs).forEach(function (name) { root.setAttribute(name, look.attrs[name]); });
        if (look.mode) {
            try { localStorage.setItem('theme', look.mode); } catch (e) {}
            root.classList.toggle('dark', look.mode === 'dark' || (look.mode === 'system' && !!media && media.matches));
        }
    };
    window.mksAppearance = { saved: look, apply: apply };
    apply();
    if (window.mksAppearanceBooted) { return; }
    window.mksAppearanceBooted = true;
    // wire:navigate copies the new page's <html> attributes (dropping ours); onSwap runs in the
    // same task as the swap, before any paint, so the look never flickers.
    document.addEventListener('livewire:navigating', function (event) {
        if (event.detail && typeof event.detail.onSwap === 'function') {
            event.detail.onSwap(function () { window.mksAppearance.apply(); });
        }
    });
    document.addEventListener('livewire:navigated', function () { window.mksAppearance.apply(); });
    if (media && media.addEventListener) {
        media.addEventListener('change', function () {
            if (window.mksAppearance.saved.mode === 'system') { window.mksAppearance.apply(); }
        });
    }
})();
JS;
    }
}
