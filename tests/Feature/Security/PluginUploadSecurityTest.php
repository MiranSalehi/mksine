<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Plugins\PluginDiscovery;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Filament\Pages\ManagePlugins;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function pluginUploadZip(string $path, array $entries): void
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

function uploadPluginZip(string $zipPath): void
{
    $page = new ManagePlugins;

    Closure::bind(
        fn () => $page->processPluginUpload($zipPath, redirect: false),
        null,
        ManagePlugins::class,
    )();
}

function actAsSuperAdmin(): User
{
    $role = config('filament-shield.super_admin.name', 'super_admin');
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

beforeEach(function (): void {
    $this->pluginsPath = PluginDiscovery::defaultPluginsPath();
    $this->zipPath = sys_get_temp_dir().'/mksine-upload-test-'.bin2hex(random_bytes(4)).'.zip';
    $this->canary = sys_get_temp_dir().'/mksine-upload-canary-'.bin2hex(random_bytes(4));
    $this->installed = [];
});

afterEach(function (): void {
    foreach ($this->installed as $dir) {
        if (is_dir($dir)) {
            ArchiveExtractor::deleteDirectory($dir);
        }
    }

    foreach ([$this->zipPath, $this->canary] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('refuses plugin uploads from users who are not super admins', function (): void {
    $this->actingAs(User::factory()->create());

    pluginUploadZip($this->zipPath, ['ztest-upload/plugin.php' => "<?php return ['id' => 'ztest-upload'];"]);

    uploadPluginZip($this->zipPath);
})->throws(AuthorizationException::class);

it('refuses plugin lifecycle actions from users who are not super admins', function (string $method): void {
    $this->actingAs(User::factory()->create());

    (new ManagePlugins)->{$method}('ztest-upload');
})->with(['installPlugin', 'activatePlugin', 'deactivatePlugin', 'uninstallPlugin', 'deletePlugin'])
    ->throws(AuthorizationException::class);

it('installs a well formed plugin into the plugins directory', function (): void {
    actAsSuperAdmin();

    $this->installed[] = $this->pluginsPath.'/ztest-upload';

    pluginUploadZip($this->zipPath, [
        'ztest-upload/plugin.php' => "<?php return ['id' => 'ztest-upload', 'name' => 'Ztest Upload', 'version' => '1.0.0'];",
        'ztest-upload/README.md' => '# Ztest',
    ]);

    uploadPluginZip($this->zipPath);

    expect(is_file($this->pluginsPath.'/ztest-upload/plugin.php'))->toBeTrue()
        ->and(is_file($this->pluginsPath.'/ztest-upload/README.md'))->toBeTrue();
});

it('never executes the manifest of a rejected archive', function (): void {
    actAsSuperAdmin();

    $manifest = sprintf(
        "<?php file_put_contents(%s, 'pwned'); return ['id' => '../ztest-escape'];",
        var_export($this->canary, true),
    );

    pluginUploadZip($this->zipPath, ['ztest-upload/plugin.php' => $manifest]);

    uploadPluginZip($this->zipPath);

    expect(file_exists($this->canary))->toBeFalse()
        ->and(is_dir(dirname($this->pluginsPath).'/ztest-escape'))->toBeFalse();
});

it('does not require anything out of the uploaded archive', function (): void {
    $source = file_get_contents((new ReflectionClass(ManagePlugins::class))->getFileName());

    expect($source)->not->toContain('require $tempManifestPath')
        ->and($source)->not->toContain('temp-manifest-');
});

it('rejects a manifest id that would escape the plugins directory', function (string $id): void {
    actAsSuperAdmin();

    $escapeTarget = dirname($this->pluginsPath).'/ztest-escape';

    pluginUploadZip($this->zipPath, [
        'ztest-upload/plugin.php' => "<?php return ['id' => ".var_export($id, true).", 'name' => 'Ztest', 'version' => '1.0.0'];",
    ]);

    uploadPluginZip($this->zipPath);

    expect(is_dir($escapeTarget))->toBeFalse()
        ->and(is_dir($this->pluginsPath.'/'.basename($id)))->toBeFalse();
})->with([
    'parent traversal' => '../ztest-escape',
    'deep traversal' => '../../ztest-escape',
    'absolute' => '/tmp/ztest-escape',
]);

it('rejects an archive whose root folder traverses out of the plugins directory', function (): void {
    actAsSuperAdmin();

    pluginUploadZip($this->zipPath, ['../plugin.php' => "<?php return ['id' => 'ztest-upload'];"]);

    uploadPluginZip($this->zipPath);

    expect(is_dir($this->pluginsPath.'/ztest-upload'))->toBeFalse();
});

it('leaves no staging directory behind after a rejected upload', function (): void {
    actAsSuperAdmin();

    pluginUploadZip($this->zipPath, ['ztest-upload/plugin.php' => "<?php return ['id' => '../escape'];"]);

    uploadPluginZip($this->zipPath);

    $staging = glob(storage_path('app/plugin-temp/staging-*')) ?: [];

    expect($staging)->toBe([]);
});
