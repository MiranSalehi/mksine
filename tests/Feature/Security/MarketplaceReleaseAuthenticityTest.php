<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Core\Marketplace\DownloadMarketplaceArchive;
use Miran\Mksine\Core\Marketplace\MarketplaceCatalogClient;
use Miran\Mksine\Core\Marketplace\MarketplaceException;
use Miran\Mksine\Core\Marketplace\MarketplaceKind;
use Miran\Mksine\Core\Marketplace\MarketplacePackage;
use Miran\Mksine\Core\Marketplace\MarketplaceReleaseTrust;

/**
 * @return array{public: string, secret: string}
 */
function marketplaceAuthenticityKeypair(): array
{
    static $pair = null;

    return $pair ??= MarketplaceReleaseTrust::generateKeypair();
}

function marketplaceAuthenticitySign(string $kind, string $packageId, string $version, string $sha256): string
{
    return MarketplaceReleaseTrust::sign(
        $kind,
        $packageId,
        $version,
        $sha256,
        marketplaceAuthenticityKeypair()['secret'],
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function marketplaceAuthenticityListing(array $overrides = []): array
{
    $payload = [
        'type' => 'plugin',
        'name' => 'Ada Logs',
        'slug' => 'ada-logs',
        'package_id' => 'ada-logs',
        'summary' => 'Structured logs.',
        'license' => 'MIT',
        'version' => '1.0.0',
        'changelog' => '',
        'url' => 'https://mksine.com/marketplace/plugins/ada-logs',
        'download_url' => 'https://mksine.com/marketplace/plugins/ada-logs/download',
        'archive_sha256' => str_repeat('a', 64),
        'archive_bytes' => 12,
        'author' => ['name' => 'Ada', 'slug' => 'ada', 'url' => 'https://mksine.com'],
        ...$overrides,
    ];

    if (! array_key_exists('archive_signature', $overrides)) {
        $payload['archive_signature'] = marketplaceAuthenticitySign(
            (string) $payload['type'],
            (string) $payload['package_id'],
            (string) $payload['version'],
            (string) $payload['archive_sha256'],
        );
    }

    return $payload;
}

beforeEach(function (): void {
    config([
        'mksine.marketplace.require_release_signature' => true,
        'mksine.marketplace.signing_public_keys' => [marketplaceAuthenticityKeypair()['public']],
    ]);
});

it('accepts a signature from a trusted key', function (): void {
    $listing = MarketplacePackage::fromApi(marketplaceAuthenticityListing(), MarketplaceKind::Plugin);

    expect($listing->archiveSignature)->toHaveLength(128);
});

it('rejects an unsigned listing when signatures are required', function (): void {
    expect(fn () => MarketplacePackage::fromApi(
        marketplaceAuthenticityListing(['archive_signature' => '']),
        MarketplaceKind::Plugin,
    ))->toThrow(MarketplaceException::class, __('mksine::marketplace.signature_missing'));
});

it('allows an unsigned listing only when the kill switch is off', function (): void {
    config(['mksine.marketplace.require_release_signature' => false]);

    $listing = MarketplacePackage::fromApi(
        marketplaceAuthenticityListing(['archive_signature' => '']),
        MarketplaceKind::Plugin,
    );

    expect($listing->archiveSignature)->toBe('');
});

it('rejects a signature from an untrusted key', function (): void {
    $foreign = MarketplaceReleaseTrust::generateKeypair();
    $signature = MarketplaceReleaseTrust::sign(
        'plugin',
        'ada-logs',
        '1.0.0',
        str_repeat('a', 64),
        $foreign['secret'],
    );

    expect(fn () => MarketplacePackage::fromApi(
        marketplaceAuthenticityListing(['archive_signature' => $signature]),
        MarketplaceKind::Plugin,
    ))->toThrow(MarketplaceException::class, __('mksine::marketplace.signature_invalid'));
});

it('rejects a plugin signature reused as a theme', function (): void {
    $payload = marketplaceAuthenticityListing([
        'type' => 'theme',
        'slug' => 'ada-theme',
        'package_id' => 'ada-logs',
        'download_url' => 'https://mksine.com/marketplace/themes/ada-theme/download',
        'archive_signature' => marketplaceAuthenticitySign('plugin', 'ada-logs', '1.0.0', str_repeat('a', 64)),
    ]);

    expect(fn () => MarketplacePackage::fromApi($payload, MarketplaceKind::Theme))
        ->toThrow(MarketplaceException::class, __('mksine::marketplace.signature_invalid'));
});

it('fails the catalog index when every listing is unsigned', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://mksine.com/api/marketplace/v1/plugins' => Http::response([
            'data' => [marketplaceAuthenticityListing(['archive_signature' => ''])],
            'meta' => ['current_page' => 1, 'next_page' => null, 'prev_page' => null],
        ]),
    ]);

    $result = app(MarketplaceCatalogClient::class)->index(MarketplaceKind::Plugin);

    expect($result->ok)->toBeFalse()
        ->and($result->error)->toBe(__('mksine::marketplace.signature_invalid'));
});

it('downloads a signed zip after verifying the bytes against the signature', function (): void {
    Http::preventStrayRequests();
    $zip = 'PK-signed-zip';
    $hash = hash('sha256', $zip);

    Http::fake([
        'https://mksine.com/api/marketplace/v1/plugins/ada-logs' => Http::response([
            'data' => marketplaceAuthenticityListing([
                'archive_sha256' => $hash,
                'archive_bytes' => strlen($zip),
            ]),
        ]),
        'https://mksine.com/marketplace/plugins/ada-logs/download' => Http::response($zip, 200, [
            'Content-Type' => 'application/zip',
        ]),
    ]);

    $path = app(DownloadMarketplaceArchive::class)->handle(MarketplaceKind::Plugin, 'ada-logs');

    expect(is_file($path))->toBeTrue()
        ->and(hash_file('sha256', $path))->toBe($hash);

    unlink($path);
});

it('rejects a redirected download instead of following it', function (): void {
    Http::preventStrayRequests();
    $zip = 'PK-evil';
    $hash = hash('sha256', $zip);

    Http::fake([
        'https://mksine.com/api/marketplace/v1/plugins/ada-logs' => Http::response([
            'data' => marketplaceAuthenticityListing([
                'archive_sha256' => $hash,
                'archive_bytes' => strlen($zip),
            ]),
        ]),
        'https://mksine.com/marketplace/plugins/ada-logs/download' => Http::response('', 302, [
            'Location' => 'https://evil.example/malware.zip',
        ]),
        'https://evil.example/malware.zip' => Http::response($zip, 200),
    ]);

    expect(fn () => app(DownloadMarketplaceArchive::class)->handle(MarketplaceKind::Plugin, 'ada-logs'))
        ->toThrow(MarketplaceException::class, __('mksine::marketplace.download_redirected'));

    Http::assertNotSent(fn ($request): bool => $request->url() === 'https://evil.example/malware.zip');
});

it('treats a redirected catalog response as unreachable', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://mksine.com/api/marketplace/v1/plugins' => Http::response('', 302, [
            'Location' => 'https://evil.example/catalog.json',
        ]),
        'https://evil.example/catalog.json' => Http::response([
            'data' => [marketplaceAuthenticityListing()],
        ]),
    ]);

    $result = app(MarketplaceCatalogClient::class)->index(MarketplaceKind::Plugin);

    expect($result->ok)->toBeFalse()
        ->and($result->error)->toBe(__('mksine::marketplace.unreachable'));

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'evil.example'));
});

it('signs a release through artisan without printing the secret', function (): void {
    $pair = MarketplaceReleaseTrust::generateKeypair();
    $hash = str_repeat('b', 64);
    $expected = MarketplaceReleaseTrust::sign('plugin', 'ada-logs', '2.0.0', $hash, $pair['secret']);

    $secretPath = sys_get_temp_dir().'/mks-mkt-sign-'.bin2hex(random_bytes(4)).'.sec';
    file_put_contents($secretPath, "# comment\n".$pair['secret']."\n");

    try {
        $code = Artisan::call('mksine:sign-marketplace-release', [
            'kind' => 'plugin',
            'package_id' => 'ada-logs',
            'version' => '2.0.0',
            'sha256' => $hash,
            '--secret' => $secretPath,
        ]);

        expect($code)->toBe(0)
            ->and(trim(Artisan::output()))->toBe($expected);
    } finally {
        @unlink($secretPath);
    }
});

it('writes a generated keypair to disk and prints only the public key', function (): void {
    $dir = sys_get_temp_dir().'/mks-mkt-keys-'.bin2hex(random_bytes(4));
    mkdir($dir, 0700);
    $secretPath = $dir.'/marketplace.sec';
    $publicPath = $dir.'/marketplace.pub';

    try {
        $code = Artisan::call('mksine:sign-marketplace-release', [
            '--generate-keypair' => true,
            '--secret' => $secretPath,
            '--public' => $publicPath,
        ]);

        $printed = trim(Artisan::output());
        $publicFromFile = MarketplaceReleaseTrust::readHexKeyFile($publicPath, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $secretFromFile = MarketplaceReleaseTrust::readHexKeyFile($secretPath, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);

        expect($code)->toBe(0)
            ->and($printed)->toBe($publicFromFile)
            ->and($printed)->toHaveLength(64)
            ->and($secretFromFile)->toHaveLength(128);

        if (PHP_OS_FAMILY !== 'Windows') {
            expect(decoct(fileperms($secretPath) & 0777))->toBe('600');
        }
    } finally {
        @unlink($secretPath);
        @unlink($publicPath);
        @rmdir($dir);
    }
});

it('ships a bundled marketplace public key', function (): void {
    $files = MarketplaceReleaseTrust::bundledPublicKeyFiles();

    expect($files)->not->toBeEmpty();

    $key = MarketplaceReleaseTrust::readHexKeyFile(
        $files[0],
        SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
    );

    expect($key)->toBeString()->toHaveLength(64)
        ->and(MarketplaceReleaseTrust::trustedPublicKeys())->toContain($key);
});
