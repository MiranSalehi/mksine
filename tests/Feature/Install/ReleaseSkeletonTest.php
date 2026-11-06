<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Support\ReleaseArchiveOptions;
use Miran\Mksine\Support\ReleaseArchiveZipper;
use Miran\Mksine\Support\ReleaseSkeleton;

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir() . '/mksine-skeleton-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->tmp);
});

it('copies only the package files a running site needs', function (string $path, bool $expected): void {
    expect(ReleaseSkeleton::shouldCopyPackagePath($path))->toBe($expected);
})->with([
    ['src/Mksine.php', true],
    ['config/mksine.php', true],
    ['resources/views/installer/run.blade.php', true],
    ['resources/views/themes/mksine/dist/app.css', true],
    ['resources/lang/fa/install.php', true],
    ['stubs/model.stub', true],
    ['LICENSE.md', true],
    ['docs/01-installation.md', false],
    ['tests/Pest.php', false],
    ['scripts/publish-release.sh', false],
    ['bin/build.js', false],
    ['node_modules/x/index.js', false],
    ['resources/views/themes/mksine/node_modules/x/index.js', false],
    ['resources/views/themes/mksine/src/app.css', false],
    ['resources/installer/mksine.php', false],
    ['resources/views/themes/mksine/package.json', false],
    ['README.md', false],
    ['CHANGELOG.md', false],
    ['phpstan.neon.dist', false],
    ['.github/workflows/docs-lint.yml', false],
]);

it('copies only the plugin files a running site needs, keeping plugin vendor folders', function (string $path, bool $expected): void {
    expect(ReleaseSkeleton::shouldCopyPluginPath($path))->toBe($expected);
})->with([
    ['ecom/plugin.php', true],
    ['ecom/composer.json', true],
    ['ecom/src/EcomPlugin.php', true],
    ['ecom/resources/dist/app.js', true],
    ['mks-booking/vendor/spatie/laravel-activitylog/src/ActivityLogger.php', true],
    ['mks-booking/vendor/.DS_Store', false],
    ['ecom/node_modules/vite/index.js', false],
    ['ecom/docs/guide.md', false],
    ['ecom/load-tests/run.js', false],
    ['ecom/src/Import.zip', false],
    ['ecom/package.json', false],
    ['ecom/vite.config.js', false],
    ['mks-booking/composer.lock', false],
    ['mks-notification/README.md', false],
    ['ecom/.DS_Store', false],
]);

it('allows only the application folders at the top of a release', function (string $path, bool $expected): void {
    expect(ReleaseSkeleton::isAllowedTopLevel($path))->toBe($expected);
})->with([
    ['app/Models/User.php', true],
    ['vendor/autoload.php', true],
    ['artisan', true],
    ['.env.example', true],
    ['composer.json', true],
    ['composer.lock', false],
    ['.cursor/rules.md', false],
    ['graphify-out/graph.json', false],
    ['AGENTS.md', false],
    ['tests/TestCase.php', false],
    ['themes/old/theme.json', false],
    ['packages/mksine/src/Mksine.php', false],
]);

it('packs nothing outside the allowlist', function (): void {
    $project = $this->tmp . '/project';
    foreach (['artisan' => '<?php', 'app/A.php' => '<?php', '.cursor/x.md' => 'x', 'prompt.md' => 'x', 'public/index.php' => '<?php', 'packages/mksine/src/M.php' => '<?php'] as $file => $contents) {
        (new Filesystem)->ensureDirectoryExists(dirname($project . '/' . $file));
        file_put_contents($project . '/' . $file, $contents);
    }

    ReleaseArchiveZipper::createArchive($project, $this->tmp . '/release.zip', new ReleaseArchiveOptions(
        includeExtensions: true,
        topLevelAllowlist: ReleaseSkeleton::TOP_LEVEL_ALLOWLIST,
    ));

    $zip = new ZipArchive;
    $zip->open($this->tmp . '/release.zip');
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();
    sort($names);

    expect($names)->toBe(['app/A.php', 'artisan', 'public/index.php']);
});

it('strips documentation from Composer packages but keeps code and licences', function (): void {
    $vendor = $this->tmp . '/vendor';
    foreach (['acme/lib/src/Lib.php', 'acme/lib/README.md', 'acme/lib/CHANGELOG.md', 'acme/lib/LICENSE.md', 'acme/lib/docs/index.rst', 'acme/lib/.github/workflow.yml', 'acme/lib/.DS_Store'] as $file) {
        (new Filesystem)->ensureDirectoryExists(dirname($vendor . '/' . $file));
        file_put_contents($vendor . '/' . $file, 'x');
    }

    ReleaseSkeleton::stripVendorDocumentation($vendor);

    expect(is_file($vendor . '/acme/lib/src/Lib.php'))->toBeTrue()
        ->and(is_file($vendor . '/acme/lib/LICENSE.md'))->toBeTrue()
        ->and(is_file($vendor . '/acme/lib/README.md'))->toBeFalse()
        ->and(is_file($vendor . '/acme/lib/CHANGELOG.md'))->toBeFalse()
        ->and(is_dir($vendor . '/acme/lib/docs'))->toBeFalse()
        ->and(is_dir($vendor . '/acme/lib/.github'))->toBeFalse()
        ->and(is_file($vendor . '/acme/lib/.DS_Store'))->toBeFalse();
});

it('gives child processes a clean environment without the maintainer settings', function (): void {
    putenv('DB_PASSWORD=maintainer-secret');
    $_ENV['APP_KEY'] = 'base64:maintainer';

    try {
        $environment = ReleaseSkeleton::cleanEnvironment();
    } finally {
        putenv('DB_PASSWORD');
        unset($_ENV['APP_KEY']);
    }

    expect($environment)->toHaveKey('DB_PASSWORD', false)
        ->and($environment)->toHaveKey('APP_KEY', false)
        ->and($environment)->not->toHaveKey('PATH')
        ->and($environment)->not->toHaveKey('HOME');
});

it('releases only published plugins', function (): void {
    expect(ReleaseSkeleton::PUBLISHED_PLUGINS)->not->toContain('ecom')
        ->and(ReleaseSkeleton::PUBLISHED_PLUGINS)->not->toContain('mks-booking')
        ->and(ReleaseSkeleton::PUBLISHED_PLUGINS)->not->toContain('mks-notification');

    foreach (['mks-redirects', 'ecom'] as $plugin) {
        (new Filesystem)->ensureDirectoryExists($this->tmp . '/plugins/' . $plugin);
        file_put_contents($this->tmp . '/plugins/' . $plugin . '/plugin.php', '<?php');
    }

    expect(ReleaseSkeleton::releasePlugins($this->tmp . '/plugins', ['mks-redirects']))->toBe(['mks-redirects'])
        ->and(fn () => ReleaseSkeleton::releasePlugins($this->tmp . '/plugins', ['mks-redirects', 'mks-missing']))->toThrow(RuntimeException::class, 'mks-missing')
        ->and(fn () => ReleaseSkeleton::releasePlugins($this->tmp . '/plugins', ['../plugins/ecom']))->toThrow(RuntimeException::class)
        ->and(fn () => ReleaseSkeleton::releasePlugins($this->tmp . '/plugins', []))->toThrow(RuntimeException::class);
});

it('finds every published plugin in this project', function (): void {
    $pluginsPath = base_path('plugins');

    if (! is_dir($pluginsPath)) {
        $this->markTestSkipped('No plugins folder in this application.');
    }

    expect(ReleaseSkeleton::releasePlugins($pluginsPath))->toBe(ReleaseSkeleton::PUBLISHED_PLUGINS);
});
