<?php

declare(strict_types=1);

/*
 * Page-builder grid renders generate breakpoint-prefixed col-span classes via PHP string concatenation,
 * which the Tailwind v4 source scanner cannot detect. We therefore embed a literal safelist in each
 * blade so themes that include the page-builder views produce CSS for those classes. If anyone removes
 * or trims that comment, this test will fail before users hit the bug in production.
 */

/**
 * @return list<string>
 */
function pageBuilderColSpanLiterals(array $prefixes): array
{
    $out = [];
    foreach ($prefixes as $prefix) {
        for ($i = 1; $i <= 12; $i++) {
            $out[] = $prefix.':col-span-'.$i;
        }
    }

    return $out;
}

test('grid_layout blade carries literal sm/md/lg col-span tokens for tailwind scanner', function (): void {
    $source = file_get_contents(base_path('packages/mksine/resources/views/page-builder/render/grid_layout.blade.php'));

    expect($source)->not->toBeFalse();

    foreach (pageBuilderColSpanLiterals(['sm', 'md', 'lg']) as $token) {
        expect(str_contains($source, $token))->toBeTrue("missing literal '{$token}' in grid_layout.blade.php");
    }

    expect($source)
        ->toContain('max-sm:col-span-12')
        ->toContain('max-md:col-span-12');
});

test('columns blade carries literal sm/md/lg col-span tokens for tailwind scanner', function (): void {
    $source = file_get_contents(base_path('packages/mksine/resources/views/page-builder/render/columns.blade.php'));

    expect($source)->not->toBeFalse();

    foreach (pageBuilderColSpanLiterals(['sm', 'md', 'lg']) as $token) {
        expect(str_contains($source, $token))->toBeTrue("missing literal '{$token}' in columns.blade.php");
    }
});
