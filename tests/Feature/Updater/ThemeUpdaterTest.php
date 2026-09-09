<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\Updaters\ThemeUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;

function zipThemeMakeZip(string $path, array $entries): void
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

beforeEach(function (): void {
    $this->themeId = 'ztest-zip-theme';
    $this->themeDir = resource_path('views/themes/'.$this->themeId);
    $this->publicTheme = public_path('themes/'.$this->themeId);

    if (is_dir($this->themeDir)) {
        ArchiveExtractor::deleteDirectory($this->themeDir);
    }

    mkdir($this->themeDir.'/dist', 0755, true);
    file_put_contents($this->themeDir.'/theme.json', json_encode([
        'name' => 'Ztest Zip Theme',
        'identifier' => $this->themeId,
        'version' => '1.0.0',
        'requires' => [
            'plugins' => ['zip-upd-missing-plugin'],
        ],
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    file_put_contents($this->themeDir.'/dist/app.css', 'body{color:black}');

    app(ThemeManager::class)->clearCache();
});

afterEach(function (): void {
    app(ThemeManager::class)->clearCache();

    if (isset($this->themeDir) && is_dir($this->themeDir)) {
        try {
            ArchiveExtractor::deleteDirectory($this->themeDir);
        } catch (Throwable) {
            // Best-effort.
        }
    }

    if (isset($this->publicTheme) && is_dir($this->publicTheme)) {
        try {
            ArchiveExtractor::deleteDirectory($this->publicTheme);
        } catch (Throwable) {
            // Best-effort.
        }
    }

    $zip = sys_get_temp_dir().'/ztest-zip-theme.zip';
    if (is_file($zip)) {
        unlink($zip);
    }
});

it('swaps a GitHub-wrapped theme ZIP and publishes lang with a positional theme argument', function (): void {
    $zipPath = sys_get_temp_dir().'/ztest-zip-theme.zip';
    $wrapper = $this->themeId.'-2.0.0';
    zipThemeMakeZip($zipPath, [
        $wrapper.'/theme.json' => json_encode([
            'name' => 'Ztest Zip Theme',
            'identifier' => $this->themeId,
            'version' => '2.0.0',
            'requires' => [
                'plugins' => ['zip-upd-missing-plugin'],
            ],
        ], JSON_THROW_ON_ERROR),
        $wrapper.'/dist/app.css' => 'body{color:red}',
    ]);

    $this->artisanCalls = [];
    Artisan::shouldReceive('call')->andReturnUsing(function (string $command, array $parameters = []): int {
        $this->artisanCalls[] = [$command, $parameters];

        return 0;
    });
    Artisan::shouldReceive('output')->andReturn('');

    $result = (new ThemeUpdater(new UpdateRunner, app(ThemeManager::class)))
        ->update($this->themeId, $zipPath);

    expect($result->success)->toBeTrue();
    expect($result->toVersion)->toBe('2.0.0');

    $json = json_decode((string) file_get_contents($this->themeDir.'/theme.json'), true);
    expect($json['version'])->toBe('2.0.0');
    expect(file_get_contents($this->themeDir.'/dist/app.css'))->toContain('color:red');

    $lang = collect($this->artisanCalls)->first(fn (array $call): bool => $call[0] === 'mks:theme-publish-lang');
    expect($lang)->not->toBeNull();
    expect($lang[1])->toBe(['theme' => $this->themeId]);
    expect($lang[1])->not->toHaveKey('--theme');

    expect($result->warnings)->not->toBeEmpty();
});
