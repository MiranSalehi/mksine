<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\Support\ThemeZipIdentity;

it('matches theme.json identifier', function (): void {
    $json = ['identifier' => 'voltech', 'name' => 'Voltech Theme', 'version' => '1.0.0'];

    expect(ThemeZipIdentity::matches('/tmp/staging/voltech', '/tmp/staging', $json, 'voltech'))->toBeTrue();
});

it('matches a GitHub-style wrapper folder', function (): void {
    $json = ['name' => 'Voltech', 'version' => '1.2.0'];

    expect(ThemeZipIdentity::folderMatchesTarget('voltech-1.2.0', 'voltech'))->toBeTrue();
    expect(ThemeZipIdentity::matches('/tmp/staging/voltech-1.2.0', '/tmp/staging', $json, 'voltech'))->toBeTrue();
});

it('matches a flat ZIP when identifier equals the target', function (): void {
    $json = ['identifier' => 'voltech', 'name' => 'Voltech', 'version' => '1.0.0'];

    expect(ThemeZipIdentity::matches('/tmp/staging', '/tmp/staging', $json, 'voltech'))->toBeTrue();
});

it('rejects a wrapping folder that does not belong to the target', function (): void {
    $json = ['name' => 'Other Theme', 'version' => '1.0.0'];

    expect(ThemeZipIdentity::matches('/tmp/staging/other-theme', '/tmp/staging', $json, 'voltech'))->toBeFalse();
});
