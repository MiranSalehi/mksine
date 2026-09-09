<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\VersionGuard;

it('accepts a strictly higher version', function (): void {
    VersionGuard::assertUpgrade('1.0.0', '1.1.0', false);

    expect(true)->toBeTrue();
});

it('rejects the same version unless reinstall is allowed', function (): void {
    config()->set('mksine.updater.allow_same_version_reinstall', false);

    expect(fn () => VersionGuard::assertUpgrade('1.2.0', '1.2.0', false))
        ->toThrow(UpdateException::class, 'Already at version');
});

it('allows the same version when allow_same_version_reinstall is true', function (): void {
    config()->set('mksine.updater.allow_same_version_reinstall', true);

    VersionGuard::assertUpgrade('1.2.0', '1.2.0', false);

    expect(true)->toBeTrue();
});

it('rejects downgrades without force', function (): void {
    expect(fn () => VersionGuard::assertUpgrade('2.0.0', '1.9.0', false))
        ->toThrow(UpdateException::class, 'Downgrade rejected');
});

it('allows any version when force is true', function (): void {
    VersionGuard::assertUpgrade('2.0.0', '1.0.0', true);
    VersionGuard::assertUpgrade('1.0.0', '1.0.0', true);

    expect(true)->toBeTrue();
});

afterEach(function (): void {
    config()->set('mksine.updater.allow_same_version_reinstall', false);
});
