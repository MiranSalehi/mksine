<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder;

use RuntimeException;

/**
 * Limits compiled Tailwind block utilities to .mksine-block.
 *
 * Tailwind's reset and utilities would otherwise restyle the active theme.
 */
final class BlockStylesScoper
{
    public function scope(string $css): string
    {
        $root = strpos($css, ':root, :host {');
        if ($root !== false) {
            $css = substr_replace($css, '.mksine-block {', $root, strlen(':root, :host {'));
        }

        $reset = strpos($css, '*, ::before, ::after, ::backdrop {');
        if ($reset !== false) {
            $css = substr_replace(
                $css,
                '.mksine-block, .mksine-block::before, .mksine-block::after {',
                $reset,
                strlen('*, ::before, ::after, ::backdrop {'),
            );
        }

        $css = $this->scopeUtilities($css);
        $css .= $this->footer();

        if (str_contains($css, ':root') || str_contains($css, 'prefers-color-scheme') || str_contains($css, '*, ::before')) {
            throw new RuntimeException('scoped css still leaks onto the page');
        }

        if (! str_contains($css, '.mksine-block .text-slate-900')) {
            throw new RuntimeException('heading color was not scoped');
        }

        return $css;
    }

    private function scopeUtilities(string $css): string
    {
        $start = strpos($css, '@layer utilities {');
        $end = strrpos($css, '@layer properties {');
        if ($start === false || $end === false || $end < $start) {
            throw new RuntimeException('utilities layer not found');
        }

        $scoped = [];
        foreach (preg_split('/\R/', substr($css, $start, $end - $start)) as $line) {
            if (preg_match('/^(  )(\.[^{]+)\{$/', $line, $match) !== 1) {
                $scoped[] = $line;

                continue;
            }

            $scoped[] = $match[1].$this->scopeSelector(trim($match[2])).' {';
        }

        return substr($css, 0, $start).implode("\n", $scoped).substr($css, $end);
    }

    private function scopeSelector(string $selector): string
    {
        $scoped = [];
        foreach (explode(',', $selector) as $part) {
            $part = trim($part);
            if (str_starts_with($part, '.')) {
                $scoped[] = '.mksine-block'.$part;
                $scoped[] = '.mksine-block '.$part;

                continue;
            }

            $scoped[] = '.mksine-block '.$part;
        }

        return implode(', ', $scoped);
    }

    private function footer(): string
    {
        return <<<'CSS'


.mksine-block {
  color: #0f172a;
  color-scheme: light;
}
.mksine-block .builder-rich-text p {
  margin-block: 0.75em;
  line-height: 1.7;
}
.mksine-block .builder-rich-text a {
  color: #d97706;
  font-weight: 500;
  text-decoration: none;
}
.mksine-block .builder-rich-text a:hover {
  text-decoration: underline;
}
.mksine-block .builder-rich-text ul,
.mksine-block .builder-rich-text ol {
  padding-inline-start: 1.25rem;
}
.mksine-block .builder-rich-text ul {
  list-style: disc;
}
.mksine-block .builder-rich-text ol {
  list-style: decimal;
}
.mksine-block[data-mksine-text] :is(h1, h2, h3, h4, h5, h6, p, li, figcaption) {
  color: inherit;
}
.mksine-block[data-mksine-bg] > :not(.mksine-canvas-tools):not(.mksine-insert) {
  background-color: transparent;
}
.mksine-block[data-mksine-align="left"] :is(.text-left, .text-right, .text-center, .text-start, .text-end, .md\:text-left, .md\:text-start, .md\:text-center, .lg\:text-start) {
  text-align: left;
}
.mksine-block[data-mksine-align="center"] :is(.text-left, .text-right, .text-center, .text-start, .text-end, .md\:text-left, .md\:text-start, .md\:text-center, .lg\:text-start) {
  text-align: center;
}
.mksine-block[data-mksine-align="right"] :is(.text-left, .text-right, .text-center, .text-start, .text-end, .md\:text-left, .md\:text-start, .md\:text-center, .lg\:text-start) {
  text-align: right;
}
.mksine-block[data-mksine-align="end"] :is(.text-left, .text-right, .text-center, .text-start, .text-end, .md\:text-left, .md\:text-start, .md\:text-center, .lg\:text-start) {
  text-align: end;
}
.mksine-block[data-mksine-align="left"] .mx-auto { margin-left: 0; margin-right: auto; }
.mksine-block[data-mksine-align="right"] .mx-auto { margin-left: auto; margin-right: 0; }
.mksine-block[data-mksine-align="end"] .mx-auto { margin-inline-start: auto; margin-inline-end: 0; }
.mksine-block[data-mksine-align="left"] :is(.w-8, .w-9, .w-10, .w-11, .w-12, .w-14, .w-16, .w-20, .w-24):not(.absolute):not(.flex > *) { margin-left: 0; margin-right: auto; }
.mksine-block[data-mksine-align="center"] :is(.w-8, .w-9, .w-10, .w-11, .w-12, .w-14, .w-16, .w-20, .w-24):not(.absolute):not(.flex > *) { margin-left: auto; margin-right: auto; }
.mksine-block[data-mksine-align="right"] :is(.w-8, .w-9, .w-10, .w-11, .w-12, .w-14, .w-16, .w-20, .w-24):not(.absolute):not(.flex > *) { margin-left: auto; margin-right: 0; }
.mksine-block[data-mksine-align="end"] :is(.w-8, .w-9, .w-10, .w-11, .w-12, .w-14, .w-16, .w-20, .w-24):not(.absolute):not(.flex > *) { margin-inline-start: auto; margin-inline-end: 0; }
.mksine-block[data-mksine-align="left"] .flex:not(.flex-col):not(.w-8):not(.w-9):not(.w-10):not(.w-11):not(.w-12):not(.w-14):not(.w-16):not(.w-20):not(.w-24) { justify-content: left; }
.mksine-block[data-mksine-align="center"] .flex:not(.flex-col):not(.w-8):not(.w-9):not(.w-10):not(.w-11):not(.w-12):not(.w-14):not(.w-16):not(.w-20):not(.w-24) { justify-content: center; }
.mksine-block[data-mksine-align="right"] .flex:not(.flex-col):not(.w-8):not(.w-9):not(.w-10):not(.w-11):not(.w-12):not(.w-14):not(.w-16):not(.w-20):not(.w-24) { justify-content: right; }
.mksine-block[data-mksine-align="end"] .flex:not(.flex-col):not(.w-8):not(.w-9):not(.w-10):not(.w-11):not(.w-12):not(.w-14):not(.w-16):not(.w-20):not(.w-24) { justify-content: end; }
.mksine-block[data-mksine-align="left"] .flex-col:is(.items-start, .items-center, .items-end) { align-items: left; }
.mksine-block[data-mksine-align="center"] .flex-col:is(.items-start, .items-center, .items-end) { align-items: center; }
.mksine-block[data-mksine-align="right"] .flex-col:is(.items-start, .items-center, .items-end) { align-items: right; }
.mksine-block[data-mksine-align="end"] .flex-col:is(.items-start, .items-center, .items-end) { align-items: end; }

CSS;
    }
}
