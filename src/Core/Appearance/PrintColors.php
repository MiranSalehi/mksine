<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

use Filament\Support\Colors\Color;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Support\SettingStore;

/**
 * Colours for printed documents (invoices, reports): always the COMPANY look's primary colour, never
 * a person's own look, so everyone's prints match. Used sparingly: title (800), header rule (700),
 * table heading background (50); text and table lines stay black and grey for mono printers.
 *
 * With "print without colour" on, nothing is emitted and print views fall back to their own
 * black/grey values (`var(--mks-print-title, #111)` …), i.e. exactly the previous prints.
 */
final class PrintColors
{
    public const string MONOCHROME_KEY = 'mksine_print_monochrome';

    public function __construct(
        private readonly AppearanceRepository $repository,
        private readonly SettingStore $settings,
    ) {}

    public function isMonochrome(): bool
    {
        return filter_var($this->settings->get(self::MONOCHROME_KEY), FILTER_VALIDATE_BOOL);
    }

    public function setMonochrome(bool $monochrome): void
    {
        Setting::query()->updateOrCreate(['key' => self::MONOCHROME_KEY], ['value' => $monochrome ? '1' : '0']);
        $this->settings->flush();
    }

    /**
     * Company primary shades for print (Filament's blue when the company keeps the default colour);
     * null when printing without colour.
     *
     * @return array<int, string>|null
     */
    public function palette(): ?array
    {
        if ($this->isMonochrome()) {
            return null;
        }

        $primary = $this->repository->company()->primary;

        if ($primary !== null) {
            return ColorScale::palette($primary)->shades;
        }

        return array_map(static fn (string $shade): string => Color::convertToHex($shade), Color::Blue);
    }

    /**
     * CSS for a print view's `<head>`: the print variables, and colour backgrounds kept on paper.
     */
    public function css(): string
    {
        $palette = $this->palette();

        if ($palette === null) {
            return '';
        }

        return ':root{'
            . '--mks-print-title:' . $palette[800] . ';'
            . '--mks-print-rule:' . $palette[700] . ';'
            . '--mks-print-head-bg:' . $palette[50] . ';'
            . '--mks-print-accent:' . $palette[600] . ';'
            . '}'
            . 'html{color-scheme:light;-webkit-print-color-adjust:exact;print-color-adjust:exact}';
    }
}
