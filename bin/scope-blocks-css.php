<?php

declare(strict_types=1);

/**
 * Scope a Tailwind build of the default blocks.
 *
 * Run from the package root:
 *   php bin/scope-blocks-css.php <compiled.css> resources/dist/blocks.css
 */

use Miran\Mksine\Core\PageBuilder\BlockStylesScoper;

require dirname(__DIR__).'/src/Core/PageBuilder/BlockStylesScoper.php';

if ($argc !== 3) {
    fwrite(STDERR, "usage: php bin/scope-blocks-css.php <source.css> <target.css>\n");
    exit(1);
}

$source = $argv[1];
$target = $argv[2];

if (! is_file($source)) {
    fwrite(STDERR, "source not found: {$source}\n");
    exit(1);
}

try {
    $css = (new BlockStylesScoper)->scope((string) file_get_contents($source));
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}

$directory = dirname($target);
if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
    fwrite(STDERR, "cannot create {$directory}\n");
    exit(1);
}

if (file_put_contents($target, $css) === false) {
    fwrite(STDERR, "cannot write {$target}\n");
    exit(1);
}
