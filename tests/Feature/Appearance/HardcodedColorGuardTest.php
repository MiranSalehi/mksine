<?php

declare(strict_types=1);

/*
 * The admin appearance (primary palette, tinted grays, radius scale, light/dark) only reaches the
 * package's custom admin screens when their CSS and Blade go through tokens: Filament's --primary-*,
 * --gray-*, --danger-*, --success-*, --warning-* and --info-* variables (and the matching Tailwind utilities),
 * the --radius-* scale, or named --mks-* tokens in resources/css/tokens.css. This guard fails on
 * literal colours, non-semantic Tailwind palettes and pixel radii in the admin files below.
 */

/**
 * Admin files and folders (relative to the package root) that must use colour and radius tokens.
 */
const HARDCODED_COLOR_GUARD_SCOPE = [
    'resources/views/filament',
    'resources/views/livewire',
    'resources/views/components',
    'resources/views/page-builder/page-builder.blade.php',
    'resources/views/page-builder/component-editor.blade.php',
    'resources/views/page-builder/components',
    'resources/views/page-builder/partials',
    'resources/views/page-builder/preview',
    'resources/views/partials/ckeditor-bootstrap.blade.php',
    'src/Filament',
    'resources/css/index.css',
    'resources/css/mks-ckeditor-filament-overrides.css',
    'resources/css/jalali-date-time-picker.css',
];

/**
 * Storefront Blade inside the scoped folders (rendered by themes, not by the admin panel).
 */
const HARDCODED_COLOR_GUARD_STOREFRONT_PATHS = [
    'resources/views/components/layouts/',
    'resources/views/components/image.blade.php',
    'resources/views/components/menu.blade.php',
    'resources/views/components/pagination.blade.php',
];

/**
 * Files allowed to hold literal colours and radii: the token definitions and print styles.
 */
const HARDCODED_COLOR_GUARD_ALLOWLIST = [
    'exact' => ['resources/css/tokens.css'],
    'contains' => ['/print/'],
    'suffixes' => ['-print.blade.php', 'print.css'],
];

/**
 * Tailwind palettes that resolve to Filament's (user-controllable) colour variables, plus keywords.
 */
const HARDCODED_COLOR_GUARD_ALLOWED_PALETTES = [
    'gray', 'primary', 'danger', 'success', 'warning', 'info',
    'white', 'black', 'transparent', 'current', 'inherit',
];

/**
 * Every built-in Tailwind palette; the ones not in the allowed list above are rejected.
 */
const HARDCODED_COLOR_GUARD_TAILWIND_PALETTES = [
    'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo',
    'violet', 'purple', 'fuchsia', 'pink', 'rose', 'slate', 'gray', 'zinc', 'neutral', 'stone',
];

/**
 * @return list<string> package-relative paths of every scanned file
 */
function hardcodedColorGuardFiles(string $packageRoot): array
{
    $files = [];

    foreach (HARDCODED_COLOR_GUARD_SCOPE as $entry) {
        $absolute = $packageRoot . '/' . $entry;

        if (is_file($absolute)) {
            $files[] = $entry;

            continue;
        }

        if (! is_dir($absolute)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (! in_array($file->getExtension(), ['php', 'css'], true)) {
                continue;
            }

            $files[] = substr($file->getPathname(), strlen($packageRoot) + 1);
        }
    }

    $files = array_filter($files, function (string $path): bool {
        foreach (HARDCODED_COLOR_GUARD_STOREFRONT_PATHS as $storefrontPath) {
            if (str_starts_with($path, $storefrontPath)) {
                return false;
            }
        }

        return ! hardcodedColorGuardIsAllowlisted($path);
    });

    sort($files);

    return array_values(array_unique($files));
}

function hardcodedColorGuardIsAllowlisted(string $path): bool
{
    if (in_array($path, HARDCODED_COLOR_GUARD_ALLOWLIST['exact'], true)) {
        return true;
    }

    foreach (HARDCODED_COLOR_GUARD_ALLOWLIST['contains'] as $fragment) {
        if (str_contains('/' . $path, $fragment)) {
            return true;
        }
    }

    foreach (HARDCODED_COLOR_GUARD_ALLOWLIST['suffixes'] as $suffix) {
        if (str_ends_with($path, $suffix)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array{function: string, hex: string, palette: string, radius: string, arbitraryRadius: string}
 */
function hardcodedColorGuardPatterns(): array
{
    $forbiddenPalettes = implode('|', array_diff(HARDCODED_COLOR_GUARD_TAILWIND_PALETTES, HARDCODED_COLOR_GUARD_ALLOWED_PALETTES));
    $prefixes = 'bg|text|border|ring|from|to|via|fill|stroke|outline|divide|shadow|accent|decoration|placeholder|caret';

    return [
        // rgb()/hsl()/oklch()… colour functions (not PHP calls such as Color::rgb() or $x->hsl()).
        'function' => '/(?<![a-z0-9$>\-]|::)(?:rgba?|hsla?|oklch|oklab|lch|lab|hwb)\(\s*([^()]*(?:\([^()]*\)[^()]*)*)\)/i',
        // #rgb, #rgba, #rrggbb, #rrggbbaa (not HTML entities like &#123;).
        'hex' => '/(?<![&\w])#(?:[0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{3,4})(?![\w-])/i',
        // Tailwind utilities (any variant, important and /opacity) or theme vars on a non-semantic palette.
        'palette' => '/(?<![\w-])(?:(?:[!a-z0-9-]+:)*!?(?:' . $prefixes . ')(?:-(?:x|y|t|r|b|l|s|e|offset))?|--color)-(?:' . $forbiddenPalettes . ')-(?:50|[1-9]00|950)(?![\w-])/',
        // border-radius / border-*-radius / --x-border-radius with a fixed length.
        'radius' => '/border(?:-[a-z]+)*-radius\s*:\s*(\d*\.?\d+)(px|rem|em)\b/',
        // rounded-[10px], rounded-t-[0.5rem] …
        'arbitraryRadius' => '/rounded(?:-[a-z]+)?-\[\d[^\]]*\]/',
    ];
}

/**
 * Whether a hex literal at $offset of $line is used as a colour (CSS, style attributes, SVG paint,
 * Tailwind arbitrary values) rather than as a fragment link, id, placeholder or plain data.
 */
function hardcodedColorGuardHexIsStyling(string $line, int $offset, bool $inCss): bool
{
    $after = substr($line, $offset);

    // ID selectors in CSS (#foo { / #foo:hover / #foo.bar / #foo > …).
    if (preg_match('/^#[0-9a-f]+\s*(?:\{|:[a-z]|[.\[>~+])/i', $after) === 1) {
        return false;
    }

    if ($inCss) {
        return true;
    }

    $before = substr($line, 0, $offset);

    // Tailwind arbitrary values: bg-[#fff], shadow-[0_0_0_1px_#000], from-[#123]/50.
    if (preg_match('/[\[_,(]$/', $before) === 1) {
        return true;
    }

    // CSS declarations in strings (color: #fff) and SVG / HTML paint attributes.
    if (preg_match('/(?:[a-z-]\s*:\s*|\b(?:fill|stroke|stop-color|color|bgcolor)\s*=\s*["\'])$/i', $before) === 1) {
        return true;
    }

    // Inside an open style="…" / :style="…" attribute.
    $styleStart = max((int) strrpos($before, 'style="'), (int) strrpos($before, "style='"));

    return $styleStart > 0 && preg_match('/style=(["\']).*\1/', substr($before, $styleStart)) !== 1;
}

/**
 * @return list<string> "line: match" violations in $contents
 */
function hardcodedColorGuardViolations(string $path, string $contents): array
{
    $patterns = hardcodedColorGuardPatterns();
    $isCssFile = str_ends_with($path, '.css');
    $inStyleBlock = false;
    $inCssComment = false;
    $violations = [];

    foreach (preg_split('/\R/', $contents) ?: [] as $index => $rawLine) {
        $lineNumber = $index + 1;
        $line = $rawLine;

        // Drop CSS comments (also multi-line) and Blade comments on the line.
        if ($inCssComment) {
            $end = strpos($line, '*/');

            if ($end === false) {
                continue;
            }

            $line = substr($line, $end + 2);
            $inCssComment = false;
        }

        if ($isCssFile || $inStyleBlock) {
            $line = (string) preg_replace('#/\*.*?\*/#', '', $line);

            if (($start = strpos($line, '/*')) !== false) {
                $line = substr($line, 0, $start);
                $inCssComment = true;
            }
        }

        $line = (string) preg_replace('/\{\{--.*?--\}\}/', '', $line);

        if (! $isCssFile && preg_match('/<style\b[^>]*>/i', $line) === 1) {
            $inStyleBlock = true;
        }

        $inCss = $isCssFile || $inStyleBlock;

        if (preg_match_all($patterns['function'], $line, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                // Variable-only channels such as rgb(var(--gray-500) / 0.5) are fine.
                if (str_starts_with(trim($match[1]), 'var(')) {
                    continue;
                }

                $violations[] = "{$lineNumber}: {$match[0]}";
            }
        }

        if (preg_match_all($patterns['hex'], $line, $matches, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($matches[0] as [$hex, $offset]) {
                if (hardcodedColorGuardHexIsStyling($line, $offset, $inCss)) {
                    $violations[] = "{$lineNumber}: {$hex}";
                }
            }
        }

        if (preg_match_all($patterns['palette'], $line, $matches) > 0) {
            foreach ($matches[0] as $utility) {
                $violations[] = "{$lineNumber}: {$utility}";
            }
        }

        if (preg_match_all($patterns['radius'], $line, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $isZero = (float) $match[1] === 0.0;
                $isPill = $match[2] === 'px' && (float) $match[1] >= 999;

                if (! $isZero && ! $isPill) {
                    $violations[] = "{$lineNumber}: {$match[0]}";
                }
            }
        }

        if (preg_match_all($patterns['arbitraryRadius'], $line, $matches) > 0) {
            foreach ($matches[0] as $radius) {
                $violations[] = "{$lineNumber}: {$radius}";
            }
        }

        if (! $isCssFile && preg_match('#</style>#i', $line) === 1) {
            $inStyleBlock = false;
        }
    }

    return $violations;
}

function hardcodedColorGuardPackageRoot(): string
{
    return dirname(__DIR__, 3);
}

it('scans the admin views, Filament classes and admin stylesheets', function (): void {
    $files = hardcodedColorGuardFiles(hardcodedColorGuardPackageRoot());

    expect(count($files))->toBeGreaterThan(50)
        ->and($files)->toContain('resources/css/index.css')
        ->and($files)->toContain('resources/views/filament/pages/console-terminal.blade.php')
        ->and($files)->toContain('resources/views/page-builder/preview/document.blade.php')
        ->and($files)->toContain('src/Filament/Pages/ManagePlugins.php')
        ->and($files)->not->toContain('resources/css/tokens.css')
        ->and($files)->not->toContain('resources/views/components/pagination.blade.php');
});

it('keeps admin colours and radii on tokens', function (): void {
    $root = hardcodedColorGuardPackageRoot();
    $report = [];

    foreach (hardcodedColorGuardFiles($root) as $path) {
        foreach (hardcodedColorGuardViolations($path, (string) file_get_contents($root . '/' . $path)) as $violation) {
            $report[] = "{$path}:{$violation}";
        }
    }

    expect($report)->toBe([], "Hard-coded colours or radii found (use Filament colour variables / semantic utilities, --radius-* or a --mks-* token in resources/css/tokens.css):\n" . implode("\n", $report));
});

it('flags hard-coded colours and radii', function (string $path, string $snippet): void {
    expect(hardcodedColorGuardViolations($path, $snippet))->not->toBe([]);
})->with([
    'hex in css' => ['a.css', '.a { color: #123456; }'],
    'short hex in css' => ['a.css', '.a { background: #fff; }'],
    'rgb in css' => ['a.css', '.a { box-shadow: 0 1px 2px rgb(0 0 0 / 0.1); }'],
    'hsl in css' => ['a.css', '.dark { --ck-color-text: hsl(0, 0%, 98%); }'],
    'oklch in css' => ['a.css', '.a { color: oklch(0.6 0.2 260); }'],
    'hex in style block' => ['a.blade.php', "<style>\n.a { border-color: #e4e4e7; }\n</style>"],
    'hex in style attribute' => ['a.blade.php', '<div style="color: #123456">'],
    'svg fill' => ['a.blade.php', '<svg fill="#000000"></svg>'],
    'arbitrary hex utility' => ['a.blade.php', '<div class="bg-[#0b0e14]/95">'],
    'arbitrary rgb shadow' => ['a.blade.php', '<div class="shadow-[0_1px_3px_0_rgb(0_0_0/0.05)]">'],
    'zinc utility' => ['a.blade.php', '<div class="dark:hover:bg-zinc-800/50">'],
    'violet utility' => ['a.php', "'bg-violet-600 text-white'"],
    'ring offset palette' => ['a.blade.php', '<div class="focus:ring-offset-slate-900">'],
    'tailwind palette var' => ['a.css', '.a { color: var(--color-zinc-300); }'],
    'pixel radius' => ['a.css', '.a { border-radius: 12px; }'],
    'rem radius' => ['a.css', '.a { border-top-left-radius: 0.625rem; }'],
    'arbitrary radius' => ['a.blade.php', '<div class="rounded-[10px]">'],
]);

it('ignores tokens, keywords and non-colour hashes', function (string $path, string $snippet): void {
    expect(hardcodedColorGuardViolations($path, $snippet))->toBe([]);
})->with([
    'semantic utilities' => ['a.blade.php', '<div class="bg-primary-600 text-gray-500 dark:bg-danger-500/10 ring-success-500 from-warning-50 to-info-100 bg-white text-black">'],
    'variables and color-mix' => ['a.css', '.a { color: var(--gray-500); background: color-mix(in oklab, var(--primary-500) 12%, transparent); }'],
    'variable-only rgb' => ['a.css', '.a { color: rgb(var(--gray-500) / 0.5); }'],
    'html entity' => ['a.blade.php', '<span>&#123; &#xfff;</span>'],
    'fragment link' => ['a.blade.php', '<a href="#add">Add</a>'],
    'hex placeholder' => ['a.blade.php', '<input placeholder="#4f46e5">'],
    'css id selector' => ['a.css', '#face:hover { color: var(--gray-900); }'],
    'css comment' => ['a.css', "/* was #6d28d9 */\n.a { color: var(--primary-700); }"],
    'php colour call' => ['a.php', '$color = Color::rgb($value); $x->hsl(1);'],
    'pill and zero radius' => ['a.css', '.a { border-radius: 9999px; } .b { border-radius: 0; } .c { border-radius: 50%; }'],
    'radius tokens' => ['a.blade.php', '<div class="rounded-lg rounded-[var(--radius-xl)]" style="border-radius: var(--radius-md)">'],
]);
