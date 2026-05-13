<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\Support\ComposerDependencyDiff;
use Miran\Mksine\Core\Updater\UpdateException;

function writeJson(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function tmpComposerDir(): string
{
    $dir = sys_get_temp_dir() . '/mks-composer-diff-' . bin2hex(random_bytes(4));
    mkdir($dir, 0755, true);

    return $dir;
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir() . '/mks-composer-diff-*') ?: [] as $dir) {
        array_map('unlink', glob($dir . '/*') ?: []);
        @rmdir($dir);
    }
});

it('reports no diff for identical require sections', function (): void {
    $dir = tmpComposerDir();
    writeJson("{$dir}/a.json", [
        'name' => 'miran/mksine',
        'require' => ['php' => '^8.2', 'filament/filament' => '^4.0'],
        'require-dev' => ['pestphp/pest' => '^2.1'],
    ]);
    writeJson("{$dir}/b.json", [
        'name' => 'miran/mksine',
        'version' => '1.2.3',
        'require' => ['php' => '^8.2', 'filament/filament' => '^4.0'],
        'require-dev' => ['pestphp/pest' => '^2.1'],
        'scripts' => ['post-autoload-dump' => 'echo hi'],
    ]);

    $diff = ComposerDependencyDiff::diff(
        json_decode(file_get_contents("{$dir}/a.json"), true),
        json_decode(file_get_contents("{$dir}/b.json"), true),
    );

    expect(ComposerDependencyDiff::hasChanges($diff))->toBeFalse();

    expect(fn () => ComposerDependencyDiff::assertNoDependencyChanges("{$dir}/a.json", "{$dir}/b.json"))
        ->not->toThrow(UpdateException::class);
});

it('rejects ZIP that adds a new require dependency', function (): void {
    $dir = tmpComposerDir();
    writeJson("{$dir}/a.json", [
        'require' => ['php' => '^8.2'],
    ]);
    writeJson("{$dir}/b.json", [
        'require' => ['php' => '^8.2', 'new/pkg' => '^1.0'],
    ]);

    expect(fn () => ComposerDependencyDiff::assertNoDependencyChanges("{$dir}/a.json", "{$dir}/b.json"))
        ->toThrow(UpdateException::class, 'dependencies changed');
});

it('rejects ZIP that removes an existing require', function (): void {
    $dir = tmpComposerDir();
    writeJson("{$dir}/a.json", [
        'require' => ['php' => '^8.2', 'vendor/pkg' => '^1.0'],
    ]);
    writeJson("{$dir}/b.json", [
        'require' => ['php' => '^8.2'],
    ]);

    expect(fn () => ComposerDependencyDiff::assertNoDependencyChanges("{$dir}/a.json", "{$dir}/b.json"))
        ->toThrow(UpdateException::class, 'dependencies changed');
});

it('rejects ZIP that changes a version constraint', function (): void {
    $dir = tmpComposerDir();
    writeJson("{$dir}/a.json", [
        'require' => ['filament/filament' => '^4.0'],
    ]);
    writeJson("{$dir}/b.json", [
        'require' => ['filament/filament' => '^4.1'],
    ]);

    expect(fn () => ComposerDependencyDiff::assertNoDependencyChanges("{$dir}/a.json", "{$dir}/b.json"))
        ->toThrow(UpdateException::class, 'dependencies changed');
});

it('accepts non-dependency composer.json changes (scripts, autoload, extra)', function (): void {
    $dir = tmpComposerDir();
    writeJson("{$dir}/a.json", [
        'require' => ['php' => '^8.2'],
        'autoload' => ['psr-4' => ['Miran\\Mksine\\' => 'src/']],
    ]);
    writeJson("{$dir}/b.json", [
        'require' => ['php' => '^8.2'],
        'autoload' => ['psr-4' => [
            'Miran\\Mksine\\' => 'src/',
            'Miran\\Mksine\\Core\\Updater\\' => 'src/Core/Updater/',
        ]],
        'scripts' => ['post-install-cmd' => 'echo hi'],
        'extra' => ['branch-alias' => []],
    ]);

    expect(fn () => ComposerDependencyDiff::assertNoDependencyChanges("{$dir}/a.json", "{$dir}/b.json"))
        ->not->toThrow(UpdateException::class);
});
