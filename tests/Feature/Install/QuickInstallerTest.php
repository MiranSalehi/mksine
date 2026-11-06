<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Support\QuickInstaller;
use Symfony\Component\Process\Process;

if (! defined('MKSINE_INSTALLER_LIBRARY')) {
    define('MKSINE_INSTALLER_LIBRARY', true);
}

require_once QuickInstaller::sourcePath();

beforeEach(function (): void {
    $this->tmp = sys_get_temp_dir() . '/mksine-quick-installer-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->tmp);
    $this->tmp = (string) realpath($this->tmp);
});

afterEach(function (): void {
    foreach ($GLOBALS['quickInstallerServers'] ?? [] as $server) {
        $server->stop(0);
    }

    $GLOBALS['quickInstallerServers'] = [];

    (new Filesystem)->deleteDirectory($this->tmp);
});

/**
 * Start `php -S` on a free port and wait until it accepts connections.
 *
 * @param  array<string, string>  $environment
 */
function quickInstallerServe(string $documentRoot, array $environment = []): string
{
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);

    $process = new Process([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $documentRoot], $documentRoot, $environment);
    $process->start();
    $GLOBALS['quickInstallerServers'][] = $process;

    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @fsockopen('127.0.0.1', $port);

        if ($connection !== false) {
            fclose($connection);

            return 'http://127.0.0.1:' . $port;
        }

        usleep(50000);
    }

    throw new RuntimeException('php -S did not start: ' . $process->getErrorOutput());
}

/**
 * A small split-layout release zip and its release.json, served from {tmp}/release.
 *
 * @return array{url: string, zip: string}
 */
function quickInstallerRelease(string $tmp, ?string $sha256 = null): array
{
    $directory = $tmp . '/release';
    (new Filesystem)->ensureDirectoryExists($directory);
    $zipPath = $directory . '/mksine-9.9.9-shared-hosting.zip';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('mksine/artisan', "<?php // artisan\n");
    $zip->addFromString('mksine/vendor/miran/mksine/src/Core/Install/Web/InstallerHandoff.php', "<?php\n");
    $zip->addFromString('mksine/.env.example', "APP_NAME=MKSine\nMKSINE_PUBLIC_PATH=../public_html\n");
    $zip->addFromString('mksine/storage/logs/.gitignore', "*\n");
    $zip->addFromString('public_html/index.php', "<?php echo 'mksine';\n");
    $zip->addFromString('public_html/.htaccess', "<IfModule mod_rewrite.c>\n    RewriteEngine On\n</IfModule>\n");
    $zip->addFromString('public_html/css/app.css', 'body{}');
    $zip->close();

    file_put_contents($directory . '/release.json', json_encode([
        'name' => 'MKSine',
        'version' => '9.9.9',
        'requirements' => ['php' => '>=7.0.0', 'extensions' => ['zip'], 'recommended_extensions' => []],
        'files' => [[
            'name' => basename($zipPath),
            'layout' => 'split',
            'for' => 'shared-hosting',
            'size' => filesize($zipPath),
            'sha256' => $sha256 ?? hash_file('sha256', $zipPath),
        ]],
    ]));

    return ['url' => quickInstallerServe($directory) . '/release.json', 'zip' => $zipPath];
}

/**
 * A host with the installer uploaded to {tmp}/home/www.
 *
 * @return array{client: Client, home: string, web: string}
 */
function quickInstallerSite(string $tmp, string $manifestUrl): array
{
    $home = $tmp . '/home';
    $web = $home . '/www';
    (new Filesystem)->ensureDirectoryExists($web);
    QuickInstaller::write($web, '9.9.9');

    $base = quickInstallerServe($web, ['MKSINE_INSTALLER_MANIFEST_URL' => $manifestUrl]);

    return [
        'client' => new Client(['base_uri' => $base, 'cookies' => true, 'http_errors' => false, 'headers' => ['Accept-Language' => 'en']]),
        'home' => $home,
        'web' => $web,
    ];
}

/**
 * Open the check page and click "Download and install".
 *
 * @param  array{client: Client, home: string, web: string}  $site
 */
function quickInstallerStart(array $site): string
{
    $check = (string) $site['client']->get('/mksine.php')->getBody();
    $site['client']->post('/mksine.php?action=start');

    return $check;
}

/**
 * @param  array{client: Client, home: string, web: string}  $site
 * @return array<string, mixed>
 */
function quickInstallerRun(array $site, ?Client $client = null): array
{
    for ($request = 0; $request < 30; $request++) {
        $response = quickInstallerStep($client ?? $site['client']);

        if (! empty($response['done']) || ! empty($response['error'])) {
            return $response;
        }
    }

    return [];
}

/**
 * @return array<string, mixed>
 */
function quickInstallerStep(Client $client): array
{
    return (array) json_decode((string) $client->post('/mksine.php?action=step', ['headers' => ['X-MKSine-Installer' => '1']])->getBody(), true);
}

it('downloads, checks and extracts a release without asking for a code, then hands over to /install', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    file_put_contents($site['web'] . '/index.html', 'Default host page');
    file_put_contents($site['web'] . '/.htaccess', "AddHandler application/x-httpd-ea-php84 .php\n");

    $check = quickInstallerStart($site);

    expect($check)->toContain('Ready to install MKSine 9.9.9')
        ->and($check)->not->toContain('disabled>')
        ->and($check)->not->toContain('SETUP-TOKEN');

    $result = quickInstallerRun($site);

    expect($result['done'] ?? false)->toBeTrue()
        ->and($result['handoff_url'])->toBe('install/handoff');

    $handoff = json_decode((string) file_get_contents($site['home'] . '/mksine/storage/mksine/installer-handoff.json'), true);

    expect($handoff['token_hash'])->toBe(hash('sha256', $result['handoff']))
        ->and($handoff['expires_at'])->toBeGreaterThan(time())
        ->and(file_get_contents($site['home'] . '/mksine/artisan'))->toBe("<?php // artisan\n")
        ->and(file_get_contents($site['home'] . '/mksine/.env.example'))->toContain('MKSINE_PUBLIC_PATH=../www')
        ->and(file_get_contents($site['web'] . '/css/app.css'))->toBe('body{}')
        ->and(file_get_contents($site['web'] . '/index.php'))->toContain('mksine')
        ->and(file_get_contents($site['web'] . '/.htaccess'))->toContain('RewriteEngine On')
        ->and(file_get_contents($site['web'] . '/.htaccess'))->toContain('AddHandler application/x-httpd-ea-php84')
        ->and(file_get_contents($site['web'] . '/index.html.mksine-old'))->toBe('Default host page')
        ->and(file_exists($site['web'] . '/index.html'))->toBeFalse()
        ->and(file_exists($site['web'] . '/mksine.php'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/.mksine-download.zip'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/.mksine-installer.json'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/SETUP-TOKEN.txt'))->toBeFalse();
});

it('writes nothing before the install is started', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);

    $site['client']->get('/mksine.php');

    expect(is_dir($site['home'] . '/mksine'))->toBeFalse();
});

it('deletes a download whose checksum does not match', function (): void {
    $release = quickInstallerRelease($this->tmp, str_repeat('0', 64));
    $site = quickInstallerSite($this->tmp, $release['url']);
    quickInstallerStart($site);

    $result = quickInstallerRun($site);

    expect($result['error'] ?? '')->toContain('checksum mismatch')
        ->and(file_exists($site['home'] . '/mksine/.mksine-download.zip'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/artisan'))->toBeFalse()
        ->and(file_exists($site['web'] . '/mksine.php'))->toBeTrue();
});

it('uses a release zip uploaded next to it when downloads are blocked', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, 'http://127.0.0.1:9/release.json');
    copy($release['zip'], $site['web'] . '/' . basename($release['zip']));

    $check = quickInstallerStart($site);

    expect($check)->toContain('uploaded by you')
        ->and($check)->toContain('cannot be checked');

    $result = quickInstallerRun($site);

    expect($result['done'] ?? false)->toBeTrue()
        ->and(file_exists($site['home'] . '/mksine/artisan'))->toBeTrue()
        ->and(file_exists($site['web'] . '/' . basename($release['zip'])))->toBeFalse();
});

it('sends an older release without the handoff route to /install', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $zip = new ZipArchive;
    $zip->open($release['zip']);
    $zip->deleteName('mksine/vendor/miran/mksine/src/Core/Install/Web/InstallerHandoff.php');
    $zip->close();
    $site = quickInstallerSite($this->tmp, 'http://127.0.0.1:9/release.json');
    copy($release['zip'], $site['web'] . '/' . basename($release['zip']));

    quickInstallerStart($site);
    $result = quickInstallerRun($site);

    expect($result['done'] ?? false)->toBeTrue()
        ->and($result['handoff_url'])->toBeNull()
        ->and($result['install_url'])->toBe('install')
        ->and(file_exists($site['home'] . '/mksine/storage/mksine/installer-handoff.json'))->toBeFalse();
});

it('refuses to start when the application folder already has files', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    (new Filesystem)->ensureDirectoryExists($site['home'] . '/mksine/app');

    $check = quickInstallerStart($site);

    expect($check)->toContain('already contains files')
        ->and($check)->toContain('disabled>')
        ->and(file_exists($site['home'] . '/mksine/.mksine-installer.json'))->toBeFalse();
});

it('lets only the browser that started the install run it, until it has been idle', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    $other = new Client(['base_uri' => (string) $site['client']->getConfig('base_uri'), 'cookies' => true, 'http_errors' => false, 'headers' => ['Accept-Language' => 'en']]);
    quickInstallerStart($site);

    expect((string) $other->get('/mksine.php')->getBody())->toContain('being installed from another browser')
        ->and(quickInstallerStep($other)['reload'] ?? false)->toBeTrue()
        ->and(json_decode((string) $site['client']->post('/mksine.php?action=step')->getBody(), true)['reload'] ?? false)->toBeTrue();

    $other->post('/mksine.php?action=takeover');
    expect(quickInstallerStep($other)['reload'] ?? false)->toBeTrue();

    touch($site['home'] . '/mksine/.mksine-installer.json', time() - 700);
    expect((string) $other->get('/mksine.php')->getBody())->toContain('Continue here');

    $other->post('/mksine.php?action=takeover');

    expect(quickInstallerRun($site, $other)['done'] ?? false)->toBeTrue()
        ->and(file_exists($site['home'] . '/mksine/artisan'))->toBeTrue();
});

it('asks to be moved when it is not in the site root', function (): void {
    $web = $this->tmp . '/home/www';
    (new Filesystem)->ensureDirectoryExists($web . '/blog');
    QuickInstaller::write($web . '/blog', '9.9.9');
    $client = new Client(['base_uri' => quickInstallerServe($web), 'http_errors' => false, 'headers' => ['Accept-Language' => 'en']]);

    expect((string) $client->get('/blog/mksine.php')->getBody())->toContain('Upload this file to the site')
        ->and(is_dir($web . '/mksine'))->toBeFalse();
});

it('maps zip entries for both layouts and rejects unsafe paths', function (): void {
    $split = mksi_target_for('split', '/home/u/public_html');
    $single = mksi_target_for('single', '/var/www/site/public');

    expect($split)->toBe(['layout' => 'split', 'app' => '/home/u/mksine', 'public' => '/home/u/public_html'])
        ->and(mksi_map_entry('mksine/vendor/autoload.php', $split))->toBe('/home/u/mksine/vendor/autoload.php')
        ->and(mksi_map_entry('public_html/css/app.css', $split))->toBe('/home/u/public_html/css/app.css')
        ->and(mksi_map_entry('other/file.txt', $split))->toBeNull()
        ->and(mksi_map_entry('public/index.php', $single))->toBe('/var/www/site/public/index.php')
        ->and(mksi_map_entry('artisan', $single))->toBe('/var/www/site/artisan')
        ->and(mksi_is_safe_entry('mksine/app/Models/User.php'))->toBeTrue()
        ->and(mksi_is_safe_entry('mksine/../../etc/passwd'))->toBeFalse()
        ->and(mksi_is_safe_entry('/etc/passwd'))->toBeFalse()
        ->and(mksi_is_safe_entry('C:/Windows/x'))->toBeFalse()
        ->and(mksi_is_safe_entry('mksine\\..\\x'))->toBeFalse();
});

it('chooses the server layout only for an otherwise empty folder around public', function (): void {
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/site/public');
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/home/public');
    file_put_contents($this->tmp . '/home/.bashrc', '');

    expect(mksi_target($this->tmp . '/site/public')['layout'])->toBe('single')
        ->and(mksi_target($this->tmp . '/home/public'))->toBe(['layout' => 'split', 'app' => $this->tmp . '/home/mksine', 'public' => $this->tmp . '/home/public']);

    // Once chosen, the layout is kept even after files appear next to the web root.
    file_put_contents($this->tmp . '/site/.mksine-installer.json', json_encode(['layout' => 'single', 'phase' => 'extract']));
    (new Filesystem)->ensureDirectoryExists($this->tmp . '/site/vendor');

    expect(mksi_target($this->tmp . '/site/public')['layout'])->toBe('single');
});

it('merges .htaccess, points MKSINE_PUBLIC_PATH at the web root and pins the download URL', function (): void {
    $laravel = "<IfModule mod_rewrite.c>\nRewriteEngine On\n</IfModule>\n";

    expect(mksi_merge_htaccess($laravel, ''))->toBe($laravel)
        ->and(mksi_merge_htaccess($laravel, $laravel))->toBe($laravel)
        ->and(mksi_merge_htaccess($laravel, "AddHandler php84 .php\n"))->toStartWith($laravel)->toEndWith("AddHandler php84 .php\n")
        ->and(mksi_patch_public_path("A=1\nMKSINE_PUBLIC_PATH=../public_html\nB=2\n", 'httpdocs'))->toBe("A=1\nMKSINE_PUBLIC_PATH=../httpdocs\nB=2\n")
        ->and(mksi_patch_public_path("A=1\n", 'www'))->toBe("A=1\nMKSINE_PUBLIC_PATH=../www\n")
        ->and(mksi_file_url('https://github.com/MiranSalehi/mksine/releases/latest/download/release.json', '1.22.1', 'mksine-1.22.1-shared-hosting.zip'))
        ->toBe('https://github.com/MiranSalehi/mksine/releases/download/v1.22.1/mksine-1.22.1-shared-hosting.zip')
        ->and(mksi_file_url('http://127.0.0.1:8000/release.json', '1.0.0', 'a.zip'))->toBe('http://127.0.0.1:8000/a.zip');
});
