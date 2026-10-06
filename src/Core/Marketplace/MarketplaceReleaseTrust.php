<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

/**
 * Authenticates a marketplace release against keys that are not controlled by the catalog API.
 *
 * SHA-256 from the catalog only proves the ZIP matches the JSON that advertised it. A
 * compromised mksine.com can publish both. The signature is Ed25519 over a canonical
 * (kind, package_id, version, sha256) message; the public key ships in this package, and
 * the secret stays offline. Redirect-following is a separate hole — host pinning is
 * meaningless if Guzzle is allowed to land on a different host.
 */
final class MarketplaceReleaseTrust
{
    public const SCHEMA = 'mksine-marketplace-v1';

    public const BUNDLED_PUBLIC_KEY_FILE = 'marketplace-v1.ed25519.pub';

    public static function message(string $kind, string $packageId, string $version, string $sha256): string
    {
        return implode("\n", [
            self::SCHEMA,
            $kind,
            $packageId,
            $version,
            strtolower($sha256),
        ]);
    }

    /**
     * @return non-empty-string
     */
    public static function sign(string $kind, string $packageId, string $version, string $sha256, string $secretKeyHex): string
    {
        self::assertSodium();

        $signature = sodium_crypto_sign_detached(
            self::message($kind, $packageId, $version, $sha256),
            self::decodeKey($secretKeyHex, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES),
        );

        return bin2hex($signature);
    }

    public static function verify(string $kind, string $packageId, string $version, string $sha256, string $signatureHex, string $publicKeyHex): bool
    {
        self::assertSodium();

        $signature = self::decodeKey($signatureHex, SODIUM_CRYPTO_SIGN_BYTES);
        $publicKey = self::decodeKey($publicKeyHex, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);

        return sodium_crypto_sign_verify_detached(
            $signature,
            self::message($kind, $packageId, $version, $sha256),
            $publicKey,
        );
    }

    public static function isRequired(): bool
    {
        return (bool) config('mksine.marketplace.require_release_signature', true);
    }

    /**
     * @throws MarketplaceException
     */
    public static function assertValid(string $kind, string $packageId, string $version, string $sha256, string $signatureHex): void
    {
        $signatureHex = strtolower(trim($signatureHex));

        if ($signatureHex === '') {
            if (self::isRequired()) {
                throw new MarketplaceException(
                    __('mksine::marketplace.signature_missing'),
                    error: 'signature_missing',
                );
            }

            return;
        }

        if (! preg_match('/^[a-f0-9]{128}$/', $signatureHex)) {
            throw new MarketplaceException(
                __('mksine::marketplace.signature_invalid'),
                error: 'signature_invalid',
            );
        }

        self::assertSodium();

        foreach (self::trustedPublicKeys() as $publicKeyHex) {
            try {
                if (self::verify($kind, $packageId, $version, $sha256, $signatureHex, $publicKeyHex)) {
                    return;
                }
            } catch (MarketplaceException $exception) {
                if ($exception->error === 'signature_unavailable') {
                    throw $exception;
                }

                continue;
            }
        }

        throw new MarketplaceException(
            __('mksine::marketplace.signature_invalid'),
            error: 'signature_invalid',
        );
    }

    /**
     * @return list<string>
     */
    public static function trustedPublicKeys(): array
    {
        $keys = [];

        foreach (self::bundledPublicKeyFiles() as $path) {
            $key = self::readHexKeyFile($path, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
            if ($key !== null) {
                $keys[] = $key;
            }
        }

        foreach ((array) config('mksine.marketplace.signing_public_keys', []) as $key) {
            if (! is_string($key)) {
                continue;
            }

            $normalized = strtolower(trim($key));
            if (preg_match('/^[a-f0-9]{64}$/', $normalized) === 1) {
                $keys[] = $normalized;
            }
        }

        return array_values(array_unique($keys));
    }

    public static function bundledKeysDirectory(): string
    {
        return dirname(__DIR__, 3).'/resources/keys';
    }

    /**
     * @return list<string>
     */
    public static function bundledPublicKeyFiles(): array
    {
        $directory = self::bundledKeysDirectory();
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.'/*.ed25519.pub') ?: [];
        sort($files);

        return array_values(array_filter($files, 'is_file'));
    }

    /**
     * @return array{public: non-empty-string, secret: non-empty-string}
     */
    public static function generateKeypair(): array
    {
        self::assertSodium();

        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => bin2hex(sodium_crypto_sign_publickey($pair)),
            'secret' => bin2hex(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    public static function readHexKeyFile(string $path, int $byteLength): ?string
    {
        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            return null;
        }

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $hex = strtolower($line);
            if (preg_match('/^[a-f0-9]+$/', $hex) === 1 && strlen($hex) === $byteLength * 2) {
                return $hex;
            }
        }

        return null;
    }

    private static function decodeKey(string $hex, int $byteLength): string
    {
        $hex = strtolower(trim($hex));
        $binary = hex2bin($hex);

        if ($binary === false || strlen($binary) !== $byteLength) {
            throw new MarketplaceException(
                __('mksine::marketplace.signature_invalid'),
                error: 'signature_invalid',
            );
        }

        return $binary;
    }

    private static function assertSodium(): void
    {
        if (! extension_loaded('sodium')) {
            throw new MarketplaceException(
                __('mksine::marketplace.signature_unavailable'),
                error: 'signature_unavailable',
            );
        }
    }
}
