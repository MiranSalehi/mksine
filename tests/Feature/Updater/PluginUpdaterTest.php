<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\Updaters\PluginUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Models\Plugin as PluginModel;

uses(RefreshDatabase::class);

function zipUpdMakeZip(string $path, array $entries): void
{
    if (file_exists($path)) {
        unlink($path);
    }

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }
    $zip->close();
}

function zipUpdManifest(string $id, string $version): string
{
    return <<<PHP
<?php

return [
    'id' => '{$id}',
    'name' => 'ZIP Updater Fixture',
    'version' => '{$version}',
    'namespace' => 'ZipUpdFix',
    'plugin_class' => 'ZipUpdFix\\\\HookPlugin',
    'autoload' => [
        'ZipUpdFix\\\\' => 'src/',
    ],
];
PHP;
}

function zipUpdHookPluginSource(): string
{
    return <<<'PHP'
<?php

declare(strict_types=1);

namespace ZipUpdFix;

use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;

class HookPlugin implements PluginInterface
{
    public function id(): string
    {
        return 'zip-upd-fix';
    }

    public function install(): void {}

    public function activate(): void {}

    public function deactivate(): void
    {
        file_put_contents(storage_path('framework/zip-upd-fix-deactivated'), 'hook');
    }

    public function uninstall(bool $deleteData = false): void {}

    public function boot(): void {}

    public function migrationsPath(): ?string
    {
        return null;
    }

    public function configPath(): ?string
    {
        return null;
    }

    public function viewsPath(): ?string
    {
        return null;
    }

    public function webRoutesPath(): ?string
    {
        return null;
    }

    public function apiRoutesPath(): ?string
    {
        return null;
    }

    public function translationsPath(): ?string
    {
        return null;
    }

    public function filamentResourcesPath(): ?string
    {
        return null;
    }

    public function filamentPagesPath(): ?string
    {
        return null;
    }

    public function filamentWidgetsPath(): ?string
    {
        return null;
    }

    public function namespace(): ?string
    {
        return 'ZipUpdFix';
    }
}
PHP;
}

function zipUpdWritePlugin(string $dir, string $id, string $version, bool $withHook = true, bool $withFailingMigration = false): void
{
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($dir.'/plugin.php', zipUpdManifest($id, $version));
    mkdir($dir.'/resources/dist', 0755, true);
    file_put_contents($dir.'/resources/dist/.gitkeep', '');

    if ($withHook) {
        mkdir($dir.'/src', 0755, true);
        file_put_contents($dir.'/src/HookPlugin.php', zipUpdHookPluginSource());
    }

    if ($withFailingMigration) {
        $migDir = $dir.'/database/migrations';
        mkdir($migDir, 0755, true);
        file_put_contents($migDir.'/2026_01_01_000000_zip_upd_fail.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('select * from zip_upd_fix_missing_table');
    }

    public function down(): void
    {
    }
};
PHP);
    }
}

beforeEach(function (): void {
    $this->originalPluginsPath = (string) config('mksine.plugins_path', 'plugins');
    $this->pluginRel = 'plugins-zip-upd-'.bin2hex(random_bytes(4));
    $this->pluginsRoot = base_path($this->pluginRel);
    $this->pluginId = 'zip-upd-fix';
    $this->pluginDir = $this->pluginsRoot.DIRECTORY_SEPARATOR.$this->pluginId;

    mkdir($this->pluginDir, 0755, true);
    zipUpdWritePlugin($this->pluginDir, $this->pluginId, '1.0.0');

    config()->set('mksine.plugins_path', $this->pluginRel);
    app()->forgetInstance(PluginManager::class);
    app(PluginManager::class)->discover(clearCache: true);

    PluginModel::query()->create([
        'plugin_id' => $this->pluginId,
        'status' => PluginModel::STATUS_ACTIVE,
        'installed_at' => now(),
        'activated_at' => now(),
    ]);

    $marker = storage_path('framework/zip-upd-fix-deactivated');
    if (is_file($marker)) {
        unlink($marker);
    }
});

afterEach(function (): void {
    $marker = storage_path('framework/zip-upd-fix-deactivated');
    if (is_file($marker)) {
        unlink($marker);
    }

    config()->set('mksine.plugins_path', $this->originalPluginsPath);
    app()->forgetInstance(PluginManager::class);

    if (isset($this->pluginsRoot) && is_dir($this->pluginsRoot)) {
        try {
            ArchiveExtractor::deleteDirectory($this->pluginsRoot);
        } catch (Throwable) {
            // Best-effort.
        }
    }

    try {
        app(PluginManager::class)->discover(clearCache: true);
    } catch (Throwable) {
        // Host cache restore is best-effort.
    }
});

it('swaps a higher-version plugin ZIP and does not call deactivate()', function (): void {
    $zipPath = $this->pluginsRoot.'/update.zip';
    zipUpdMakeZip($zipPath, [
        $this->pluginId.'/plugin.php' => zipUpdManifest($this->pluginId, '1.1.0'),
        $this->pluginId.'/src/HookPlugin.php' => zipUpdHookPluginSource(),
        $this->pluginId.'/resources/dist/.gitkeep' => '',
    ]);

    $result = (new PluginUpdater(new UpdateRunner, app(PluginManager::class)))
        ->update($this->pluginId, $zipPath);

    expect($result->success)->toBeTrue();
    expect($result->toVersion)->toBe('1.1.0');

    $onDisk = include $this->pluginDir.'/plugin.php';
    expect($onDisk['version'])->toBe('1.1.0');

    expect(is_file(storage_path('framework/zip-upd-fix-deactivated')))->toBeFalse();

    $row = PluginModel::query()->where('plugin_id', $this->pluginId)->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe(PluginModel::STATUS_INSTALLED);
});

it('does not succeed when plugin migrations fail', function (): void {
    $zipPath = $this->pluginsRoot.'/bad-migrate.zip';
    $entries = [
        $this->pluginId.'/plugin.php' => zipUpdManifest($this->pluginId, '1.2.0'),
        $this->pluginId.'/src/HookPlugin.php' => zipUpdHookPluginSource(),
        $this->pluginId.'/resources/dist/.gitkeep' => '',
        $this->pluginId.'/database/migrations/2026_01_01_000000_zip_upd_fail.php' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('select * from zip_upd_fix_missing_table');
    }

    public function down(): void
    {
    }
};
PHP,
    ];
    zipUpdMakeZip($zipPath, $entries);

    $result = (new PluginUpdater(new UpdateRunner, app(PluginManager::class)))
        ->update($this->pluginId, $zipPath);

    expect($result->success)->toBeFalse();
    expect($result->errorPhase)->toBe(UpdateException::PHASE_POST);
    expect($result->dbPossiblyDirty)->toBeTrue();

    $row = PluginModel::query()->where('plugin_id', $this->pluginId)->first();
    expect($row->hasBootFailed())->toBeTrue();
});

it('rejects a composer.json require without vendor/', function (): void {
    $zipPath = $this->pluginsRoot.'/no-vendor.zip';
    zipUpdMakeZip($zipPath, [
        $this->pluginId.'/plugin.php' => zipUpdManifest($this->pluginId, '1.3.0'),
        $this->pluginId.'/composer.json' => json_encode([
            'name' => 'mks/zip-upd-fix',
            'require' => [
                'php' => '^8.2',
                'illuminate/support' => '^12.0',
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    $result = (new PluginUpdater(new UpdateRunner, app(PluginManager::class)))
        ->update($this->pluginId, $zipPath);

    expect($result->success)->toBeFalse();
    expect($result->errorMessage)->toContain('vendor/');

    $onDisk = include $this->pluginDir.'/plugin.php';
    expect($onDisk['version'])->toBe('1.0.0');
});
