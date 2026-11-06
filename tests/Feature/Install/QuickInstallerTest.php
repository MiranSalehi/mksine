<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Core\Install\Web\SetupToken;
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

function quickInstallerCsrf(string $html): string
{
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $match);

    return $match[1] ?? '';
}

/**
 * Enter the setup token and start the install; returns the CSRF value.
 *
 * @param  array{client: Client, home: string, web: string}  $site
 */
function quickInstallerStart(array $site): string
{
    $site['client']->get('/mksine-installer.php');
    $token = strtok((string) file_get_contents($site['home'] . '/mksine/SETUP-TOKEN.txt'), "\n");

    $check = (string) $site['client']->post('/mksine-installer.php?action=verify', ['form_params' => ['token' => strtolower(str_replace('-', ' ', (string) $token))]])->getBody();
    $csrf = quickInstallerCsrf($check);

    expect($check)->toContain('Ready to install MKSine 9.9.9')
        ->and($check)->not->toContain('disabled>');

    $site['client']->post('/mksine-installer.php?action=start', ['form_params' => ['csrf' => $csrf]]);

    return $csrf;
}

/**
 * @param  array{client: Client, home: string, web: string}  $site
 * @return array<string, mixed>
 */
function quickInstallerRun(array $site, string $csrf): array
{
    for ($request = 0; $request < 30; $request++) {
        $response = json_decode((string) $site['client']->post('/mksine-installer.php?action=step', ['form_params' => ['csrf' => $csrf]])->getBody(), true);

        if (! is_array($response) || ! empty($response['done']) || ! empty($response['error'])) {
            return (array) $response;
        }
    }

    return [];
}

it('downloads, checks and extracts a release, then hands over to /install', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    file_put_contents($site['web'] . '/index.html', 'Default host page');
    file_put_contents($site['web'] . '/.htaccess', "AddHandler application/x-httpd-ea-php84 .php\n");

    $tokenPage = (string) $site['client']->get('/mksine-installer.php')->getBody();
    expect($tokenPage)->toContain('Confirm that this is your site')
        ->and($tokenPage)->toContain('mksine/SETUP-TOKEN.txt');

    $code = strtok((string) file_get_contents($site['home'] . '/mksine/SETUP-TOKEN.txt'), "\n");
    expect((new SetupToken(new Filesystem, $site['home'] . '/mksine'))->verify((string) $code))->toBeTrue();

    $wrong = (string) $site['client']->post('/mksine-installer.php?action=verify', ['form_params' => ['token' => 'WRONG']])->getBody();
    expect($wrong)->toContain('The code is not correct.');

    $result = quickInstallerRun($site, quickInstallerStart($site));

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
        ->and(file_exists($site['web'] . '/mksine-installer.php'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/.mksine-download.zip'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/.mksine-installer.json'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/SETUP-TOKEN.txt'))->toBeTrue();
});

it('deletes a download whose checksum does not match', function (): void {
    $release = quickInstallerRelease($this->tmp, str_repeat('0', 64));
    $site = quickInstallerSite($this->tmp, $release['url']);

    $result = quickInstallerRun($site, quickInstallerStart($site));

    expect($result['error'] ?? '')->toContain('checksum mismatch')
        ->and(file_exists($site['home'] . '/mksine/.mksine-download.zip'))->toBeFalse()
        ->and(file_exists($site['home'] . '/mksine/artisan'))->toBeFalse()
        ->and(file_exists($site['web'] . '/mksine-installer.php'))->toBeTrue();
});

it('uses a release zip uploaded next to it when downloads are blocked', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, 'http://127.0.0.1:9/release.json');
    copy($release['zip'], $site['web'] . '/' . basename($release['zip']));

    $site['client']->get('/mksine-installer.php');
    $token = strtok((string) file_get_contents($site['home'] . '/mksine/SETUP-TOKEN.txt'), "\n");
    $check = (string) $site['client']->post('/mksine-installer.php?action=verify', ['form_params' => ['token' => (string) $token]])->getBody();

    expect($check)->toContain('uploaded by you')
        ->and($check)->toContain('cannot be checked');

    $site['client']->post('/mksine-installer.php?action=start', ['form_params' => ['csrf' => quickInstallerCsrf($check)]]);
    $result = quickInstallerRun($site, quickInstallerCsrf($check));

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

    $site['client']->get('/mksine-installer.php');
    $token = strtok((string) file_get_contents($site['home'] . '/mksine/SETUP-TOKEN.txt'), "\n");
    $check = (string) $site['client']->post('/mksine-installer.php?action=verify', ['form_params' => ['token' => (string) $token]])->getBody();
    $site['client']->post('/mksine-installer.php?action=start', ['form_params' => ['csrf' => quickInstallerCsrf($check)]]);
    $result = quickInstallerRun($site, quickInstallerCsrf($check));

    expect($result['done'] ?? false)->toBeTrue()
        ->and($result['handoff_url'])->toBeNull()
        ->and($result['install_url'])->toBe('install')
        ->and(file_exists($site['home'] . '/mksine/storage/mksine/installer-handoff.json'))->toBeFalse();
});

it('refuses to start when the application folder already has files', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    (new Filesystem)->ensureDirectoryExists($site['home'] . '/mksine/app');

    $site['client']->get('/mksine-installer.php');
    $token = strtok((string) file_get_contents($site['home'] . '/mksine/SETUP-TOKEN.txt'), "\n");
    $check = (string) $site['client']->post('/mksine-installer.php?action=verify', ['form_params' => ['token' => (string) $token]])->getBody();

    expect($check)->toContain('already contains files')
        ->and($check)->toContain('disabled>');

    $site['client']->post('/mksine-installer.php?action=start', ['form_params' => ['csrf' => quickInstallerCsrf($check)]]);

    expect(json_decode((string) file_get_contents($site['home'] . '/mksine/.mksine-installer.json'), true)['phase'])->toBe('new');
});

it('ignores requests without the setup token', function (): void {
    $release = quickInstallerRelease($this->tmp);
    $site = quickInstallerSite($this->tmp, $release['url']);
    $site['client']->get('/mksine-installer.php');

    $step = json_decode((string) $site['client']->post('/mksine-installer.php?action=step', ['form_params' => ['csrf' => 'x']])->getBody(), true);
    $site['client']->post('/mksine-installer.php?action=start', ['form_params' => ['csrf' => 'x']]);

    expect($step['reload'])->toBeTrue()
        ->and(json_decode((string) file_get_contents($site['home'] . '/mksine/.mksine-installer.json'), true)['phase'])->toBe('new');
});

it('asks to be moved when it is not in the site root', function (): void {
    $web = $this->tmp . '/home/www';
    (new Filesystem)->ensureDirectoryExists($web . '/blog');
    QuickInstaller::write($web . '/blog', '9.9.9');
    $client = new Client(['base_uri' => quickInstallerServe($web), 'http_errors' => false, 'headers' => ['Accept-Language' => 'en']]);

    expect((string) $client->get('/blog/mksine-installer.php')->getBody())->toContain('Upload this file to the site')
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
        ->and(mksi_file_url('http://127.0.0.1:8000/release.json', '1.0.0', 'a.zip'))->toBe('http://127.0.0.1:8000/a.zip')
        ->and(mksi_generate_token())->toMatch('/^[A-HJKMNP-Z2-9]{4}-[A-HJKMNP-Z2-9]{4}-[A-HJKMNP-Z2-9]{4}$/');
});
