<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Marketplace\MarketplaceException;
use Miran\Mksine\Core\Marketplace\MarketplaceKind;
use Miran\Mksine\Core\Marketplace\MarketplaceReleaseTrust;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'mksine:sign-marketplace-release', description: 'Sign a marketplace ZIP (kind, package_id, version, sha256) with an offline Ed25519 key')]
class SignMarketplaceReleaseCommand extends Command
{
    protected $signature = 'mksine:sign-marketplace-release
                            {kind? : plugin or theme}
                            {package_id? : Catalog package_id}
                            {version? : Release version}
                            {sha256? : Hex SHA-256 of the ZIP}
                            {--secret= : Secret key hex, or a path to a .sec file}
                            {--public= : Destination path for the public key when generating}
                            {--generate-keypair : Write a new Ed25519 keypair instead of signing}';

    protected $description = 'Sign a marketplace release for the catalog API, or generate an offline Ed25519 keypair. The secret is never printed.';

    public function handle(): int
    {
        if ($this->option('generate-keypair')) {
            return $this->generateKeypair();
        }

        return $this->signRelease();
    }

    private function signRelease(): int
    {
        $kindInput = trim((string) $this->argument('kind'));
        $packageId = trim((string) $this->argument('package_id'));
        $version = trim((string) $this->argument('version'));
        $sha256 = strtolower(trim((string) $this->argument('sha256')));

        if ($kindInput === '' || $packageId === '' || $version === '' || $sha256 === '') {
            $this->error('kind, package_id, version, and sha256 are required unless --generate-keypair is set.');

            return self::FAILURE;
        }

        try {
            $kind = MarketplaceKind::fromCatalog($kindInput)->value;
        } catch (Throwable) {
            $this->error('kind must be plugin or theme.');

            return self::FAILURE;
        }

        if (preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            $this->error('sha256 must be a 64-character hex digest.');

            return self::FAILURE;
        }

        $secret = $this->secretKeyHex();
        if ($secret === null) {
            $this->error('Pass --secret with a 128-character hex secret or a path to a .sec file. Do not keep that file in the CMS repo.');

            return self::FAILURE;
        }

        try {
            $signature = MarketplaceReleaseTrust::sign($kind, $packageId, $version, $sha256, $secret);
        } catch (MarketplaceException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line($signature);

        return self::SUCCESS;
    }

    private function generateKeypair(): int
    {
        $secretPath = trim((string) $this->option('secret'));
        if ($secretPath === '' || preg_match('/^[a-f0-9]{128}$/', strtolower($secretPath)) === 1) {
            $this->error('Pass --secret=/path/to/marketplace.sec so the secret is written to a file, not printed.');

            return self::FAILURE;
        }

        try {
            $pair = MarketplaceReleaseTrust::generateKeypair();
        } catch (MarketplaceException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->writeKeyFile($secretPath, $pair['secret'], secret: true)) {
            return self::FAILURE;
        }

        $publicPath = trim((string) $this->option('public'));
        if ($publicPath !== '' && ! $this->writeKeyFile($publicPath, $pair['public'], secret: false)) {
            return self::FAILURE;
        }

        $this->line($pair['public']);

        return self::SUCCESS;
    }

    private function secretKeyHex(): ?string
    {
        $secret = trim((string) $this->option('secret'));
        if ($secret === '') {
            return null;
        }

        if (is_file($secret)) {
            return MarketplaceReleaseTrust::readHexKeyFile($secret, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
        }

        $hex = strtolower($secret);

        return preg_match('/^[a-f0-9]{128}$/', $hex) === 1 ? $hex : null;
    }

    private function writeKeyFile(string $path, string $hex, bool $secret): bool
    {
        $directory = dirname($path);
        if ($directory !== '.' && ! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->error('Could not create '.($secret ? 'secret' : 'public').' key directory.');

            return false;
        }

        $header = $secret
            ? "# MKSine marketplace Ed25519 secret. Keep offline. Never commit.\n"
            : "# MKSine marketplace Ed25519 public key (hex).\n";

        if (@file_put_contents($path, $header.$hex."\n") === false) {
            $this->error('Could not write '.($secret ? 'secret' : 'public').' key file.');

            return false;
        }

        if ($secret) {
            @chmod($path, 0600);
        }

        return true;
    }
}
