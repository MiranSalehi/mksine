{{--
    Company colours for a print view: <x-mksine::print.colors /> inside <head>.
    Use the variables with your current value as fallback, e.g. color: var(--mks-print-title, #111);
    so "print without colour" keeps the old black/grey print exactly.
    Variables: --mks-print-title (800), --mks-print-rule (700), --mks-print-head-bg (50), --mks-print-accent (600).
--}}
@php($printCss = app(\Miran\Mksine\Core\Appearance\PrintColors::class)->css())
@if ($printCss !== '')
    <style data-mks-print-colors>{!! $printCss !!}</style>
@endif
