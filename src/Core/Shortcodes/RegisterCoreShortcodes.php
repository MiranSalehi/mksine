<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Support\MksDateFormatter;

final class RegisterCoreShortcodes
{
    public function register(): void
    {
        Hooks::addShortcode(
            'year',
            $this->renderYear(...),
            priority: 10,
            catalog: new ShortcodeCatalogEntry(
                tag: 'year',
                label: __('mksine::shortcodes.catalog.year.label'),
                description: __('mksine::shortcodes.catalog.year.description'),
                example: '[year]',
            ),
        );

        Hooks::addShortcode(
            'site_name',
            $this->renderSiteName(...),
            priority: 10,
            catalog: new ShortcodeCatalogEntry(
                tag: 'site_name',
                label: __('mksine::shortcodes.catalog.site_name.label'),
                description: __('mksine::shortcodes.catalog.site_name.description'),
                example: '[site_name]',
            ),
        );
    }

    /**
     * @param  array<string, string>  $attrs
     */
    private function renderYear(array $attrs, ?string $content, ShortcodeContext $context): string
    {
        if (MksDateFormatter::isShamsi()) {
            $year = MksDateFormatter::format(now(), 'yyyy');

            return $year ?? (string) now()->year;
        }

        return (string) now()->year;
    }

    /**
     * @param  array<string, string>  $attrs
     */
    private function renderSiteName(array $attrs, ?string $content, ShortcodeContext $context): string
    {
        $name = mks_setting('site_name');

        if (is_string($name) && trim($name) !== '') {
            return e(trim($name));
        }

        return e((string) config('app.name', 'MKSine'));
    }
}
