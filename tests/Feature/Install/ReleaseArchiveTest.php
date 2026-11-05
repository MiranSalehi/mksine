<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Support\ReleaseArchiveBuildRoots;
use Miran\Mksine\Support\ReleaseArchiveOptions;
use Miran\Mksine\Support\ReleaseArchiveStaging;
use Miran\Mksine\Support\ReleaseArchiveZipper;
use Miran\Mksine\Support\ReleaseManifest;

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir() . '/mksine-release-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->tmp);
});

/**
 * @param  array<string, string>  $files
 */
function releaseProject(string $root, array $files): void
{
    foreach ($files as $path => $contents) {
        (new Filesystem)->ensureDirectoryExists(dirname($root . '/' . $path));
        file_put_contents($root . '/' . $path, $contents);
    }
}

/**
 * @return list<string>
 */
function zipEntries(string $zipPath): array
{
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = (string) $zip->getNameIndex($i);
    }
    $zip->close();
    sort($names);

    return $names;
}

it('ships plugins and themes in a full archive but not in a core-only one', function (string $path): void {
    config(['mksine.release_archive.include_theme_paths' => []]);

    expect(ReleaseArchiveBuildRoots::shouldIncludeInZip($path, includeExtensions: true))->toBeTrue()
        ->and(ReleaseArchiveBuildRoots::shouldIncludeInZip($path))->toBeFalse();
})->with([
    'plugins/mks-booking/plugin.php',
    'resources/views/themes/voltech/theme.json',
    'packages/mksine/resources/views/themes/mksine/theme.json',
    'public/themes/voltech/dist/app.css',
    'public/plugins/ecom/dist/app.js',
]);

it('never ships local data, backups, OS metadata or the setup token', function (string $path, bool $includeExtensions): void {
    expect(ReleaseArchiveBuildRoots::shouldIncludeInZip($path, $includeExtensions))->toBeFalse();
})->with([
    'database/database.sqlite',
    'database/database.sqlite-journal',
    'mksine-setup-database.sql',
    'app/Models/User.php.mksine-backup-20260630130805',
    'resources/.DS_Store',
    'mksine-setup/.setup-token',
    'mksine-setup/index.php',
    'SETUP-TOKEN.txt',
    'storage/mksine/installed.json',
    'storage/mksine/install-state.json',
    'storage/mksine/installer-owner.json',
    'storage/custom-cache/data.bin',
    '.env',
    'public/uploads/photo.png',
])->with([true, false]);

it('keeps the normal application files in both archive kinds', function (bool $includeExtensions): void {
    foreach (['artisan', 'app/Models/User.php', 'database/migrations/0001_create_users.php', 'public/index.php', '.env.example'] as $path) {
        expect(ReleaseArchiveBuildRoots::shouldIncludeInZip($path, $includeExtensions))->toBeTrue();
    }
})->with([true, false]);

it('discovers theme and plugin build roots only for full archives', function (): void {
    $build = json_encode(['scripts' => ['build' => 'vite build']]);
    releaseProject($this->tmp, [
        'package.json' => $build,
        'packages/mksine/package.json' => $build,
        'resources/views/themes/voltech/package.json' => $build,
        'plugins/zeta/package.json' => $build,
        'plugins/alpha/package.json' => $build,
        'plugins/no-build/package.json' => json_encode(['scripts' => ['dev' => 'vite']]),
    ]);
    config(['mksine.plugins_path' => 'plugins']);

    expect(ReleaseArchiveBuildRoots::discover($this->tmp))->toBe([$this->tmp . '/packages/mksine', $this->tmp])
        ->and(ReleaseArchiveBuildRoots::discover($this->tmp, includeExtensions: true))->toBe([
            $this->tmp . '/packages/mksine',
            $this->tmp . '/resources/views/themes/voltech',
            $this->tmp . '/plugins/alpha',
            $this->tmp . '/plugins/zeta',
            $this->tmp,
        ]);
});

it('maps project paths into the split shared-hosting layout', function (): void {
    $options = new ReleaseArchiveOptions(includeExtensions: true, layout: ReleaseArchiveOptions::LAYOUT_SPLIT);

    expect($options->entryName('artisan'))->toBe('mksine/artisan')
        ->and($options->entryName('public/build/manifest.json'))->toBe('public_html/build/manifest.json')
        ->and($options->entryName('public/index.php'))->toBeNull()
        ->and($options->entryName('packages/mksine/src/Mksine.php'))->toBeNull()
        ->and($options->entryName('vendor/miran/mksine/src/Mksine.php'))->toBe('mksine/vendor/miran/mksine/src/Mksine.php')
        ->and((new ReleaseArchiveOptions)->entryName('public/index.php'))->toBe('public/index.php');
});

it('rejects unknown layouts and unsafe directory names', function (array $arguments): void {
    expect(fn () => new ReleaseArchiveOptions(...$arguments))->toThrow(InvalidArgumentException::class);
})->with([
    'layout' => [['layout' => 'nested']],
    'traversal' => [['appDirectory' => '..']],
    'slash' => [['publicDirectory' => 'a/b']],
    'same names' => [['appDirectory' => 'site', 'publicDirectory' => 'site']],
]);

it('builds a split archive with a generated front controller', function (): void {
    $project = $this->tmp . '/project';
    releaseProject($project, [
        'artisan' => '<?php',
        '.env.example' => "APP_NAME=Laravel\n",
        'public/index.php' => '<?php // stock',
        'public/.htaccess' => 'RewriteEngine On',
        'public/build/manifest.json' => '{}',
        'packages/mksine/src/Mksine.php' => '<?php',
        'vendor/autoload.php' => '<?php',
        'database/database.sqlite' => 'secret data',
    ]);
    $zipPath = $this->tmp . '/split.zip';

    ReleaseArchiveZipper::createArchive($project, $zipPath, new ReleaseArchiveOptions(includeExtensions: true, layout: ReleaseArchiveOptions::LAYOUT_SPLIT, appDirectory: 'app'));

    expect(zipEntries($zipPath))->toBe([
        'app/.env.example',
        'app/.htaccess',
        'app/artisan',
        'app/vendor/autoload.php',
        'public_html/.htaccess',
        'public_html/build/manifest.json',
        'public_html/index.php',
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $index = (string) $zip->getFromName('public_html/index.php');
    $env = (string) $zip->getFromName('app/.env.example');
    $zip->close();

    expect($index)->toContain("dirname(__DIR__).'/app'")
        ->and($index)->toContain('$app->usePublicPath(__DIR__);')
        ->and($env)->toContain("APP_NAME=Laravel\n")
        ->and($env)->toContain('MKSINE_PUBLIC_PATH=../public_html');

    $generated = $this->tmp . '/index-check.php';
    file_put_contents($generated, $index);
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($generated), $output, $exitCode);
    expect($exitCode)->toBe(0);
});

it('stages everything except vendor, keeping the path-repository package source', function (): void {
    expect(ReleaseArchiveStaging::shouldStage('vendor/autoload.php', true))->toBeFalse()
        ->and(ReleaseArchiveStaging::shouldStage('packages/mksine/src/Mksine.php', false))->toBeTrue()
        ->and(ReleaseArchiveStaging::shouldStage('packages/mksine/resources/views/themes/mksine/theme.json', false))->toBeTrue()
        ->and(ReleaseArchiveStaging::shouldStage('packages/mksine/node_modules/x/index.js', true))->toBeFalse()
        ->and(ReleaseArchiveStaging::shouldStage('packages/mksine/vendor/autoload.php', true))->toBeFalse()
        ->and(ReleaseArchiveStaging::shouldStage('composer.lock', true))->toBeTrue()
        ->and(ReleaseArchiveStaging::shouldStage('.env', true))->toBeFalse();

    releaseProject($this->tmp . '/project', [
        'composer.json' => '{}',
        'vendor/autoload.php' => '<?php',
        'packages/mksine/src/Mksine.php' => '<?php',
    ]);

    $copied = ReleaseArchiveStaging::copyProject($this->tmp . '/project', $this->tmp . '/staging', includeExtensions: true);

    expect($copied)->toBe(2)
        ->and(is_file($this->tmp . '/staging/composer.json'))->toBeTrue()
        ->and(is_file($this->tmp . '/staging/packages/mksine/src/Mksine.php'))->toBeTrue()
        ->and(is_dir($this->tmp . '/staging/vendor'))->toBeFalse();
});

it('describes the archive in a dry run and rejects an unknown layout', function (): void {
    Artisan::call('mks:release-archive', ['--dry-run' => true, '--layout' => 'split', '--core-only' => true]);
    $output = Artisan::output();

    expect($output)->toContain('core only')
        ->and($output)->toContain('split (mksine/ + public_html/)')
        ->and(Artisan::call('mks:release-archive', ['--dry-run' => true, '--layout' => 'nested']))->toBe(1);
});

it('describes a release with sizes, checksums and requirements', function (): void {
    releaseProject($this->tmp, ['shared.zip' => 'shared-bytes', 'server.zip' => 'server-bytes!']);
    $shared = $this->tmp . '/' . ReleaseManifest::fileName('1.22.0', ReleaseArchiveOptions::LAYOUT_SPLIT);
    $server = $this->tmp . '/' . ReleaseManifest::fileName('1.22.0', ReleaseArchiveOptions::LAYOUT_SINGLE);
    rename($this->tmp . '/shared.zip', $shared);
    rename($this->tmp . '/server.zip', $server);

    $manifest = ReleaseManifest::build('1.22.0', [
        ReleaseArchiveOptions::LAYOUT_SPLIT => $shared,
        ReleaseArchiveOptions::LAYOUT_SINGLE => $server,
    ], new DateTimeImmutable('2026-11-05T10:00:00+00:00'));

    expect($manifest['version'])->toBe('1.22.0')
        ->and($manifest['released_at'])->toBe('2026-11-05T10:00:00+00:00')
        ->and($manifest['requirements']['php'])->toBe('>=8.2.0')
        ->and($manifest['files'][0])->toBe([
            'name' => 'mksine-1.22.0-shared-hosting.zip',
            'layout' => 'split',
            'for' => 'shared-hosting',
            'size' => 12,
            'sha256' => hash('sha256', 'shared-bytes'),
        ])
        ->and($manifest['files'][1]['name'])->toBe('mksine-1.22.0-server.zip')
        ->and(ReleaseManifest::checksums($manifest))->toBe(
            hash('sha256', 'shared-bytes') . '  mksine-1.22.0-shared-hosting.zip' . "\n"
            . hash('sha256', 'server-bytes!') . '  mksine-1.22.0-server.zip' . "\n"
        );
});
