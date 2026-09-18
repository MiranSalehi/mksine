<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Support\Marketplace;
use Throwable;

final class DownloadMarketplaceArchive
{
    public function __construct(private MarketplaceCatalogClient $catalog) {}

    public function handle(MarketplaceKind $kind, string $slug): string
    {
        $listing = $this->catalog->show($kind, $slug);
        $this->assertTrustedDownloadUrl($listing->downloadUrl);

        $maxBytes = $this->maxDownloadBytes();
        if ($listing->archiveBytes > 0 && $listing->archiveBytes > $maxBytes) {
            throw new MarketplaceException(__('mksine::marketplace.archive_too_large'));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'mks-mkt-');
        if ($tempPath === false) {
            throw new MarketplaceException(__('mksine::marketplace.download_failed'));
        }

        try {
            $response = Http::timeout((int) config('mksine.marketplace.download_timeout', 60))
                ->connectTimeout((int) config('mksine.marketplace.connect_timeout', 5))
                ->retry(2, 250, throw: false)
                ->withUserAgent(Marketplace::userAgent())
                ->withOptions(['sink' => $tempPath])
                ->get($listing->downloadUrl)
                ->throw();
        } catch (ConnectionException|RequestException|Throwable) {
            @unlink($tempPath);

            throw new MarketplaceException(__('mksine::marketplace.download_failed'));
        }

        if (! $response->successful()) {
            @unlink($tempPath);

            throw new MarketplaceException(__('mksine::marketplace.download_failed'));
        }

        $size = filesize($tempPath);
        if ($size === false || $size <= 0 || $size > $maxBytes) {
            @unlink($tempPath);

            throw new MarketplaceException(__('mksine::marketplace.archive_too_large'));
        }

        $hash = hash_file('sha256', $tempPath);
        if (! is_string($hash) || ! hash_equals($listing->archiveSha256, $hash)) {
            @unlink($tempPath);

            throw new MarketplaceException(__('mksine::marketplace.checksum_mismatch'));
        }

        return $tempPath;
    }

    private function assertTrustedDownloadUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $expectedHost = strtolower((string) parse_url(Marketplace::siteUrl(), PHP_URL_HOST));

        $allowedSchemes = ['https'];
        if (app()->environment(['local', 'testing'])) {
            $allowedSchemes[] = 'http';
        }

        if ($host === '' || $expectedHost === '' || $host !== $expectedHost || ! in_array($scheme, $allowedSchemes, true)) {
            throw new MarketplaceException(__('mksine::marketplace.untrusted_download'));
        }
    }

    private function maxDownloadBytes(): int
    {
        $mb = (int) config('mksine.updater.max_zip_size_mb', 100);

        return max(1, $mb) * 1024 * 1024;
    }
}
