<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Filament\Pages\ThemeManager;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function themeUploadZip(string $path, array $entries): void
{
    if (is_file($path)) {
        unlink($path);
    }

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }

    $zip->close();
}

function uploadThemeZip(string $zipPath): void
{
    $page = new ThemeManager;

    Closure::bind(
        fn () => $page->processThemeUpload($zipPath, redirect: false),
        null,
        ThemeManager::class,
    )();
}

function actAsThemeSuperAdmin(): User
{
    $role = config('filament-shield.super_admin.name', 'super_admin');
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

beforeEach(function (): void {
    $this->themesPath = resource_path('views/themes');
    $this->zipPath = sys_get_temp_dir().'/mksine-theme-upload-'.bin2hex(random_bytes(4)).'.zip';
    $this->installed = [];
});

afterEach(function (): void {
    foreach ($this->installed as $dir) {
        if (is_dir($dir)) {
            ArchiveExtractor::deleteDirectory($dir);
        }
    }

    if (is_file($this->zipPath)) {
        unlink($this->zipPath);
    }

    app(ThemeManagerService::class)->clearCache();
});

it('refuses theme uploads from users who are not super admins', function (): void {
    $this->actingAs(User::factory()->create());

    themeUploadZip($this->zipPath, ['ztest-theme/theme.json' => json_encode(['name' => 'Ztest Theme'])]);

    uploadThemeZip($this->zipPath);
})->throws(AuthorizationException::class);

it('refuses theme activation and deletion from users who are not super admins', function (string $method): void {
    $this->actingAs(User::factory()->create());

    (new ThemeManager)->{$method}('ztest-theme');
})->with(['activateTheme', 'deleteTheme'])->throws(AuthorizationException::class);

it('installs a well formed theme into the themes directory', function (): void {
    actAsThemeSuperAdmin();

    $this->installed[] = $this->themesPath.'/ztest-upload-theme';

    themeUploadZip($this->zipPath, [
        'ztest-upload-theme/theme.json' => json_encode(['name' => 'Ztest Upload Theme', 'version' => '1.0.0']),
        'ztest-upload-theme/index.blade.php' => 'hello',
    ]);

    uploadThemeZip($this->zipPath);

    expect(is_file($this->themesPath.'/ztest-upload-theme/theme.json'))->toBeTrue()
        ->and(is_file($this->themesPath.'/ztest-upload-theme/index.blade.php'))->toBeTrue();
});

it('slugs an unsafe theme name instead of using it as a path', function (): void {
    actAsThemeSuperAdmin();

    $this->installed[] = $this->themesPath.'/etc-passwd-ztest';

    themeUploadZip($this->zipPath, [
        'theme.json' => json_encode(['name' => '../../../etc/passwd ztest', 'version' => '1.0.0']),
    ]);

    uploadThemeZip($this->zipPath);

    expect(is_dir($this->themesPath.'/etc-passwd-ztest'))->toBeTrue()
        ->and(is_dir(dirname($this->themesPath).'/passwd ztest'))->toBeFalse();
});

it('rejects an archive whose root folder traverses out of the themes directory', function (): void {
    actAsThemeSuperAdmin();

    themeUploadZip($this->zipPath, ['../theme.json' => json_encode(['name' => 'Ztest Escape Theme'])]);

    uploadThemeZip($this->zipPath);

    expect(is_dir(dirname($this->themesPath).'/ztest-escape-theme'))->toBeFalse()
        ->and(is_dir($this->themesPath.'/ztest-escape-theme'))->toBeFalse();
});

it('leaves no staging directory behind after a rejected upload', function (): void {
    actAsThemeSuperAdmin();

    themeUploadZip($this->zipPath, ['ztest-theme/theme.json' => '{ not json']);

    uploadThemeZip($this->zipPath);

    expect(glob(storage_path('app/theme-temp/staging-*')) ?: [])->toBe([]);
});

it('refuses to build a custom asset path that escapes the storage directory', function (string $method): void {
    app(ThemeManagerService::class)->{$method}('../../../public/ztest-evil', 'js');
})->with(['getCustomStoragePath', 'getCustomContent'])->throws(InvalidArgumentException::class);

it('refuses to build an extra assets path that escapes the storage directory', function (): void {
    app(ThemeManagerService::class)->getExtraAssetsStoragePath('../../../public/ztest-evil');
})->throws(InvalidArgumentException::class);
