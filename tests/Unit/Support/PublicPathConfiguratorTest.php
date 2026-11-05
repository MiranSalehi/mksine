<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Support\PublicPathConfigurator;

beforeEach(function (): void {
    $this->root = realpath(sys_get_temp_dir()) . '/mksine-public-path-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->root . '/project');
    (new Filesystem)->ensureDirectoryExists($this->root . '/public_html');
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->root);
});

it('returns null when no path is configured', function (mixed $configured): void {
    expect(PublicPathConfigurator::resolve($this->root . '/project', $configured))->toBeNull();
})->with([null, '', '   ', false]);

it('resolves a path relative to the project root', function (): void {
    expect(PublicPathConfigurator::resolve($this->root . '/project', '../public_html'))
        ->toBe($this->root . '/public_html');
});

it('resolves an absolute path', function (): void {
    expect(PublicPathConfigurator::resolve($this->root . '/project', $this->root . '/public_html'))
        ->toBe($this->root . '/public_html');
});

it('ignores a directory that does not exist', function (): void {
    expect(PublicPathConfigurator::resolve($this->root . '/project', '../missing'))->toBeNull();
});

it('points the application public path at the configured directory', function (): void {
    $original = app()->publicPath();

    PublicPathConfigurator::apply(app(), $this->root . '/public_html');

    expect(public_path())->toBe($this->root . '/public_html');

    app()->usePublicPath($original);
});

it('moves disk roots and storage links that were built from the old public path', function (): void {
    $original = app()->publicPath();
    config([
        'filesystems.disks.uploads.root' => $original . '/uploads',
        'filesystems.disks.local.root' => storage_path('app/private'),
        'filesystems.links' => [$original . '/storage' => storage_path('app/public')],
    ]);

    PublicPathConfigurator::apply(app(), $this->root . '/public_html');

    expect(config('filesystems.disks.uploads.root'))->toBe($this->root . '/public_html/uploads')
        ->and(config('filesystems.disks.local.root'))->toBe(storage_path('app/private'))
        ->and(config('filesystems.links'))->toBe([$this->root . '/public_html/storage' => storage_path('app/public')]);

    app()->usePublicPath($original);
});
