<?php

declare(strict_types=1);

/**
 * Documentation lint: enforces that every Markdown page under docs/
 * (excluding archive/ and internal/) appears exactly once in _nav.yml,
 * that every nav entry exists on disk, and that every page starts with
 * a YAML front-matter block including a non-empty `title:` field.
 *
 * The parser is intentionally minimal (regex over `path:` lines) so we
 * don't introduce a YAML dependency just for this test.
 */

use Symfony\Component\Finder\Finder;

function mksine_docs_root(): string
{
    return realpath(__DIR__ . '/../docs');
}

function mksine_nav_paths(): array
{
    $navFile = mksine_docs_root() . '/_nav.yml';
    expect(is_file($navFile))->toBeTrue('_nav.yml must exist');

    $contents = file_get_contents($navFile);
    $paths = [];

    foreach (preg_split('/\R/', $contents) as $line) {
        if (preg_match('/path:\s*([^\s,}]+)/', $line, $m)) {
            $paths[] = $m[1];
        }
    }

    return $paths;
}

function mksine_doc_files(): array
{
    $root = mksine_docs_root();
    $finder = (new Finder())
        ->files()
        ->in($root)
        ->name('*.md')
        ->exclude(['archive', 'internal']);

    $rels = [];
    foreach ($finder as $file) {
        $rels[] = ltrim(str_replace($root, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
    }
    sort($rels);

    return $rels;
}

it('has _nav.yml entries for every docs page', function () {
    $diskPaths = mksine_doc_files();
    $navPaths = mksine_nav_paths();

    $orphans = array_values(array_diff($diskPaths, $navPaths));

    expect($orphans)->toBe(
        [],
        "Orphan documentation files (on disk, not in _nav.yml):\n  - " . implode("\n  - ", $orphans)
    );
});

it('every _nav.yml entry exists on disk', function () {
    $diskPaths = mksine_doc_files();
    $navPaths = mksine_nav_paths();

    $missing = array_values(array_diff($navPaths, $diskPaths));

    expect($missing)->toBe(
        [],
        "Missing documentation files (in _nav.yml, not on disk):\n  - " . implode("\n  - ", $missing)
    );
});

it('lists every doc exactly once in _nav.yml', function () {
    $navPaths = mksine_nav_paths();
    $counts = array_count_values($navPaths);
    $duplicates = [];

    foreach ($counts as $path => $count) {
        if ($count > 1) {
            $duplicates[] = "{$path} (x{$count})";
        }
    }

    expect($duplicates)->toBe(
        [],
        "Duplicate _nav.yml entries:\n  - " . implode("\n  - ", $duplicates)
    );
});

it('every doc starts with YAML front matter and a title', function () {
    $root = mksine_docs_root();
    $offenders = [];

    foreach (mksine_doc_files() as $rel) {
        $contents = file_get_contents($root . DIRECTORY_SEPARATOR . $rel);

        if (! str_starts_with($contents, "---\n") && ! str_starts_with($contents, "---\r\n")) {
            $offenders[] = "{$rel}: missing opening --- delimiter";
            continue;
        }

        $end = strpos($contents, "\n---", 4);
        if ($end === false) {
            $offenders[] = "{$rel}: missing closing --- delimiter";
            continue;
        }

        $fm = substr($contents, 4, $end - 4);
        if (! preg_match('/^title:\s*\S/m', $fm)) {
            $offenders[] = "{$rel}: front matter missing non-empty `title:` field";
        }
    }

    expect($offenders)->toBe(
        [],
        "Front-matter violations:\n  - " . implode("\n  - ", $offenders)
    );
});
