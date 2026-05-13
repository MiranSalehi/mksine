<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\UpdateException;

function makeZip(string $path, array $entries): void
{
    if (file_exists($path)) {
        unlink($path);
    }

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }

    $zip->close();
}

function tmpDir(string $label = 'mks-extractor-test'): string
{
    $dir = sys_get_temp_dir() . '/' . $label . '-' . bin2hex(random_bytes(4));
    mkdir($dir, 0755, true);

    return $dir;
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir() . '/mks-extractor-test-*') ?: [] as $dir) {
        try {
            ArchiveExtractor::deleteDirectory($dir);
        } catch (Throwable) {
            // Best-effort cleanup.
        }
    }
});

it('rejects ZIP entries containing "../" path traversal', function (): void {
    $zipPath = tmpDir() . '/evil.zip';
    makeZip($zipPath, [
        'plugin.php' => '<?php return [];',
        '../evil.php' => '<?php echo "boom"; ?>',
    ]);

    $staging = tmpDir();

    expect(fn () => ArchiveExtractor::extract($zipPath, $staging))
        ->toThrow(UpdateException::class, 'Path traversal');
});

it('rejects ZIP entries with absolute paths', function (): void {
    $zipPath = tmpDir() . '/abs.zip';
    makeZip($zipPath, [
        '/etc/passwd.copy' => 'nope',
    ]);

    $staging = tmpDir();

    expect(fn () => ArchiveExtractor::extract($zipPath, $staging))
        ->toThrow(UpdateException::class, 'Absolute path');
});

it('rejects ZIP entries with null bytes in their names', function (): void {
    $zipPath = tmpDir() . '/nullbyte.zip';
    makeZip($zipPath, [
        "plugin\0.php" => '<?php return [];',
    ]);

    $staging = tmpDir();

    expect(fn () => ArchiveExtractor::extract($zipPath, $staging))
        ->toThrow(UpdateException::class, 'Null byte');
});

it('extracts safe ZIP and reports single-root content dir', function (): void {
    $zipPath = tmpDir() . '/safe.zip';
    makeZip($zipPath, [
        'my-plugin/plugin.php' => "<?php\n\nreturn ['id' => 'my-plugin', 'name' => 'My', 'version' => '1.0.0'];\n",
        'my-plugin/src/Plugin.php' => '<?php namespace My;',
    ]);

    $staging = tmpDir();

    $root = ArchiveExtractor::extract($zipPath, $staging);

    expect(basename($root))->toBe('my-plugin');
    expect(is_file($root . '/plugin.php'))->toBeTrue();
});

it('extracts a ZIP whose files sit at the archive root', function (): void {
    $zipPath = tmpDir() . '/flat.zip';
    makeZip($zipPath, [
        'plugin.php' => "<?php\nreturn ['id' => 'flat', 'name' => 'Flat', 'version' => '1.0.0'];\n",
        'src/Plugin.php' => '<?php',
    ]);

    $staging = tmpDir();

    $root = ArchiveExtractor::extract($zipPath, $staging);

    expect(realpath($root))->toBe(realpath($staging));
    expect(is_file($root . '/plugin.php'))->toBeTrue();
});

it('fails fast when staging dir is not empty', function (): void {
    $zipPath = tmpDir() . '/ok.zip';
    makeZip($zipPath, ['a.txt' => 'x']);

    $staging = tmpDir();
    file_put_contents($staging . '/leftover.txt', 'old');

    expect(fn () => ArchiveExtractor::extract($zipPath, $staging))
        ->toThrow(UpdateException::class, 'Staging directory is not empty');
});
