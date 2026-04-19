<?php

declare(strict_types=1);

/**
 * Standalone documentation lint.
 *
 * Run with: php packages/mksine/scripts/lint-docs.php
 *
 * Exits 0 if every Markdown page under packages/mksine/docs (excluding
 * archive/ and internal/) appears exactly once in _nav.yml, every nav
 * entry exists on disk, and every page has YAML front matter with a
 * non-empty `title:` field. Exits 1 with a human-readable diagnostic
 * otherwise.
 *
 * No external dependencies. Safe to run in CI without composer install
 * for the package.
 */

$docsRoot = realpath(__DIR__ . '/../docs');
if ($docsRoot === false) {
    fwrite(STDERR, "ERROR: docs/ directory not found.\n");
    exit(1);
}

$navFile = $docsRoot . '/_nav.yml';
if (! is_file($navFile)) {
    fwrite(STDERR, "ERROR: _nav.yml not found at {$navFile}.\n");
    exit(1);
}

$navPaths = [];
foreach (preg_split('/\R/', (string) file_get_contents($navFile)) as $line) {
    if (preg_match('/path:\s*([^\s,}]+)/', $line, $m)) {
        $navPaths[] = $m[1];
    }
}

$diskPaths = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docsRoot, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if (! $f->isFile() || $f->getExtension() !== 'md') {
        continue;
    }
    $rel = ltrim(str_replace($docsRoot, '', $f->getRealPath()), DIRECTORY_SEPARATOR);
    if (str_starts_with($rel, 'archive' . DIRECTORY_SEPARATOR) || str_starts_with($rel, 'internal' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $diskPaths[] = $rel;
}
sort($diskPaths);

$errors = [];

$orphans = array_values(array_diff($diskPaths, $navPaths));
if ($orphans !== []) {
    $errors[] = "Orphan documentation files (on disk, not in _nav.yml):\n  - " . implode("\n  - ", $orphans);
}

$missing = array_values(array_diff($navPaths, $diskPaths));
if ($missing !== []) {
    $errors[] = "Missing documentation files (in _nav.yml, not on disk):\n  - " . implode("\n  - ", $missing);
}

$dupCounts = array_filter(array_count_values($navPaths), fn (int $n) => $n > 1);
if ($dupCounts !== []) {
    $list = [];
    foreach ($dupCounts as $path => $n) {
        $list[] = "{$path} (x{$n})";
    }
    $errors[] = "Duplicate _nav.yml entries:\n  - " . implode("\n  - ", $list);
}

$fmOffenders = [];
foreach ($diskPaths as $rel) {
    $c = (string) file_get_contents($docsRoot . DIRECTORY_SEPARATOR . $rel);

    if (! str_starts_with($c, "---\n") && ! str_starts_with($c, "---\r\n")) {
        $fmOffenders[] = "{$rel}: missing opening --- delimiter";
        continue;
    }

    $end = strpos($c, "\n---", 4);
    if ($end === false) {
        $fmOffenders[] = "{$rel}: missing closing --- delimiter";
        continue;
    }

    $fm = substr($c, 4, $end - 4);
    if (! preg_match('/^title:\s*\S/m', $fm)) {
        $fmOffenders[] = "{$rel}: front matter missing non-empty `title:` field";
    }
}
if ($fmOffenders !== []) {
    $errors[] = "Front-matter violations:\n  - " . implode("\n  - ", $fmOffenders);
}

if ($errors !== []) {
    fwrite(STDERR, "Documentation lint FAILED:\n\n" . implode("\n\n", $errors) . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "Documentation lint OK (%d nav entries, %d disk files).\n",
    count($navPaths),
    count($diskPaths),
));
exit(0);
