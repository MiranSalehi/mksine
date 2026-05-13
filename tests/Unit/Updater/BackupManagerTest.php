<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\UpdateTarget;

function makeBackupsFixture(): array
{
    $root = sys_get_temp_dir() . '/mks-backup-test-' . bin2hex(random_bytes(4));
    mkdir($root, 0755, true);

    $targetParent = $root . '/plugins';
    mkdir($targetParent, 0755, true);

    // Fake target exists so dirname($targetPath) resolves correctly.
    $targetPath = $targetParent . '/my-plugin';
    mkdir($targetPath, 0755, true);

    return [$root, $targetPath];
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir() . '/mks-backup-test-*') ?: [] as $dir) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
});

it('generates backup paths with timestamps and versions', function (): void {
    [, $target] = makeBackupsFixture();
    $manager = new BackupManager(UpdateTarget::Plugin, 'my-plugin');

    $p1 = $manager->newBackupPath($target, '1.2.0');
    $p2 = $manager->newBackupPath($target, null);

    expect($p1)->toContain('my-plugin-')->toContain('-v1.2.0');
    expect($p2)->toContain('my-plugin-');
    expect($p2)->not->toContain('-v');
});

it('prunes backups older than the keep limit and never deletes unrelated entries', function (): void {
    [, $target] = makeBackupsFixture();
    $manager = new BackupManager(UpdateTarget::Plugin, 'my-plugin');
    $root = $manager->ensureRoot($target);

    // Create five fake backups with distinct mtimes.
    $paths = [];
    foreach (range(1, 5) as $i) {
        $path = $root . '/my-plugin-backup-' . $i;
        mkdir($path, 0755, true);
        touch($path, time() - (5 - $i) * 60); // i=1 oldest, i=5 newest
        $paths[$i] = $path;
    }

    // Unrelated entry: must NOT be pruned.
    $unrelated = $root . '/other-plugin-backup-1';
    mkdir($unrelated, 0755, true);

    $removed = $manager->prune($target, 2);

    expect(count($removed))->toBe(3);
    expect(is_dir($paths[1]))->toBeFalse();
    expect(is_dir($paths[2]))->toBeFalse();
    expect(is_dir($paths[3]))->toBeFalse();
    expect(is_dir($paths[4]))->toBeTrue();
    expect(is_dir($paths[5]))->toBeTrue();
    expect(is_dir($unrelated))->toBeTrue();
});

it('finds the latest backup by mtime', function (): void {
    [, $target] = makeBackupsFixture();
    $manager = new BackupManager(UpdateTarget::Plugin, 'my-plugin');
    $root = $manager->ensureRoot($target);

    mkdir($root . '/my-plugin-old', 0755, true);
    touch($root . '/my-plugin-old', time() - 3600);

    mkdir($root . '/my-plugin-new', 0755, true);
    touch($root . '/my-plugin-new', time());

    expect(basename((string) $manager->latestBackup($target)))->toBe('my-plugin-new');
});

it('returns null when no backup exists for the identifier', function (): void {
    [, $target] = makeBackupsFixture();
    $manager = new BackupManager(UpdateTarget::Plugin, 'nonexistent');

    expect($manager->latestBackup($target))->toBeNull();
});
