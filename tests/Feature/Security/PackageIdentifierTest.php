<?php

declare(strict_types=1);

use Miran\Mksine\Support\FilesystemPath;
use Miran\Mksine\Support\PackageIdentifier;

it('accepts identifiers that are safe directory names', function (string $identifier): void {
    expect(PackageIdentifier::isValid($identifier))->toBeTrue();
})->with(['mks-backup', 'ecom', 'theme_v2', 'a', 'a1', str_repeat('a', 64)]);

it('rejects identifiers that could escape the install root', function (mixed $identifier): void {
    expect(PackageIdentifier::isValid($identifier))->toBeFalse();
})->with([
    'traversal' => '..',
    'traversal segment' => '../evil',
    'absolute' => '/etc/cron.d/evil',
    'nested' => 'a/b',
    'backslash' => 'a\\b',
    'leading dot' => '.hidden',
    'leading hyphen' => '-leading',
    'uppercase' => 'MyPlugin',
    'space' => 'my plugin',
    'nul byte' => "evil\0",
    'empty' => '',
    'too long' => str_repeat('a', 65),
]);

it('rejects non-string identifiers', function (): void {
    expect(PackageIdentifier::isValid(['mks-backup']))->toBeFalse()
        ->and(PackageIdentifier::isValid(null))->toBeFalse()
        ->and(PackageIdentifier::isValid(42))->toBeFalse()
        ->and(PackageIdentifier::isSafeSegment(null))->toBeFalse();
});

it('derives an identifier from a human readable name', function (string $name, ?string $expected): void {
    expect(PackageIdentifier::fromName($name))->toBe($expected);
})->with([
    ['MKSine Default', 'mksine-default'],
    ['  Voltech  ', 'voltech'],
    ['Theme 2.1', 'theme-2-1'],
    ['../../../etc/passwd', 'etc-passwd'],
    ['...', null],
    ['', null],
    ['   ', null],
]);

it('treats existing on-disk names as safe segments while still blocking traversal', function (): void {
    expect(PackageIdentifier::isSafeSegment('MyTheme'))->toBeTrue()
        ->and(PackageIdentifier::isSafeSegment('theme.v2'))->toBeTrue()
        ->and(PackageIdentifier::isSafeSegment('../../public/app'))->toBeFalse()
        ->and(PackageIdentifier::isSafeSegment('a/b'))->toBeFalse()
        ->and(PackageIdentifier::isSafeSegment('a\\b'))->toBeFalse()
        ->and(PackageIdentifier::isSafeSegment('.hidden'))->toBeFalse()
        ->and(PackageIdentifier::isSafeSegment(''))->toBeFalse();
});

it('throws when asserting an unsafe segment', function (): void {
    PackageIdentifier::assertSafeSegment('../escape');
})->throws(InvalidArgumentException::class);

describe('FilesystemPath::isWithin', function (): void {
    beforeEach(function (): void {
        $this->root = sys_get_temp_dir().'/mksine-within-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/plugins/mks-backup', 0755, true);
        mkdir($this->root.'/plugins-backup/evil', 0755, true);
    });

    afterEach(function (): void {
        exec('rm -rf '.escapeshellarg($this->root));
    });

    it('accepts a descendant', function (): void {
        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins/mks-backup'))->toBeTrue();
    });

    it('rejects a sibling directory sharing the base prefix', function (): void {
        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins-backup/evil'))->toBeFalse();
    });

    it('rejects the base itself', function (): void {
        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins'))->toBeFalse();
    });

    it('rejects a traversal that climbs back out', function (): void {
        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins/../plugins-backup'))->toBeFalse();
    });

    it('rejects paths that do not exist', function (): void {
        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins/missing'))->toBeFalse();
    });

    it('rejects a symlink that points outside the base', function (): void {
        symlink($this->root.'/plugins-backup/evil', $this->root.'/plugins/link');

        expect(FilesystemPath::isWithin($this->root.'/plugins', $this->root.'/plugins/link'))->toBeFalse();
    });
});
