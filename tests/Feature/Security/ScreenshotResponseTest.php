<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Miran\Mksine\Core\Theme\ThemeManager;

const SCREENSHOT_SAFE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 8"><rect width="8" height="8" fill="#0af"/></svg>';
const SCREENSHOT_SCRIPTED_SVG = '<svg xmlns="http://www.w3.org/2000/svg"><script>fetch("/admin")</script></svg>';

/**
 * Writes a throwaway theme into the configured themes directory.
 */
function makeScreenshotTheme(string $identifier, string $screenshot, string $contents): string
{
    $path = resource_path('views/themes').'/'.$identifier;

    File::ensureDirectoryExists($path);
    File::put($path.'/theme.json', json_encode([
        'name' => $identifier,
        'version' => '1.0.0',
        'screenshot' => $screenshot,
    ]));
    File::put($path.'/'.$screenshot, $contents);

    app(ThemeManager::class)->clearCache();

    return $path;
}

afterEach(function (): void {
    foreach (($this->screenshotThemePaths ?? []) as $path) {
        File::deleteDirectory($path);
    }

    app(ThemeManager::class)->clearCache();
});

it('serves a clean theme screenshot with hardening headers', function (): void {
    $identifier = 'ztest-clean-'.substr(md5(uniqid()), 0, 8);
    $this->screenshotThemePaths = [makeScreenshotTheme($identifier, 'screenshot.svg', SCREENSHOT_SAFE_SVG)];

    $this->get(route('mksine.theme.screenshot', ['identifier' => $identifier]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
});

it('refuses to serve a scripted theme screenshot', function (): void {
    $identifier = 'ztest-xss-'.substr(md5(uniqid()), 0, 8);
    $this->screenshotThemePaths = [makeScreenshotTheme($identifier, 'screenshot.svg', SCREENSHOT_SCRIPTED_SVG)];

    $this->get(route('mksine.theme.screenshot', ['identifier' => $identifier]))
        ->assertNotFound();
});

it('still serves a raster screenshot', function (): void {
    $identifier = 'ztest-png-'.substr(md5(uniqid()), 0, 8);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $this->screenshotThemePaths = [makeScreenshotTheme($identifier, 'screenshot.png', $png)];

    $this->get(route('mksine.theme.screenshot', ['identifier' => $identifier]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
