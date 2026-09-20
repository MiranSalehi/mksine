<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

final readonly class MarketplacePackage
{
    public function __construct(
        public MarketplaceKind $kind,
        public string $name,
        public string $slug,
        public string $packageId,
        public string $summary,
        public string $license,
        public string $version,
        public string $changelog,
        public ?string $publishedAt,
        public string $url,
        public string $downloadUrl,
        public string $archiveSha256,
        public int $archiveBytes,
        public string $authorName,
        public string $authorSlug,
        public string $authorUrl,
        public ?string $imageUrl = null,
        public int $downloads = 0,
        public ?float $ratingAverage = null,
        public int $ratingCount = 0,
        public ?string $categoryName = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromApi(array $payload, MarketplaceKind $expected): self
    {
        $type = (string) ($payload['type'] ?? '');
        $kind = match ($type) {
            'plugin' => MarketplaceKind::Plugin,
            'theme' => MarketplaceKind::Theme,
            default => throw new MarketplaceException('The catalog returned an invalid listing type.'),
        };

        if ($kind !== $expected) {
            throw new MarketplaceException('The catalog returned a listing of the wrong type.');
        }

        $slug = (string) ($payload['slug'] ?? '');
        $packageId = (string) ($payload['package_id'] ?? '');
        $downloadUrl = (string) ($payload['download_url'] ?? '');
        $sha256 = strtolower((string) ($payload['archive_sha256'] ?? ''));

        if ($slug === '' || $packageId === '' || $downloadUrl === '' || ! preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            throw new MarketplaceException('The catalog listing is incomplete.');
        }

        $author = is_array($payload['author'] ?? null) ? $payload['author'] : [];
        $category = is_array($payload['category'] ?? null) ? $payload['category'] : [];
        $categoryName = trim((string) ($category['name'] ?? ''));
        $ratingCount = max(0, (int) ($payload['rating_count'] ?? 0));
        $ratingAverage = self::optionalRatingAverage($payload['rating_average'] ?? null, $ratingCount);

        return new self(
            kind: $kind,
            name: (string) ($payload['name'] ?? $slug),
            slug: $slug,
            packageId: $packageId,
            summary: (string) ($payload['summary'] ?? ''),
            license: (string) ($payload['license'] ?? ''),
            version: (string) ($payload['version'] ?? ''),
            changelog: (string) ($payload['changelog'] ?? ''),
            publishedAt: isset($payload['published_at']) ? (string) $payload['published_at'] : null,
            url: (string) ($payload['url'] ?? ''),
            downloadUrl: $downloadUrl,
            archiveSha256: $sha256,
            archiveBytes: (int) ($payload['archive_bytes'] ?? 0),
            authorName: (string) ($author['name'] ?? ''),
            authorSlug: (string) ($author['slug'] ?? ''),
            authorUrl: (string) ($author['url'] ?? ''),
            imageUrl: self::optionalHttpUrl($payload['image_url'] ?? $payload['screenshot_url'] ?? null),
            downloads: max(0, (int) ($payload['downloads'] ?? 0)),
            ratingAverage: $ratingAverage,
            ratingCount: $ratingCount,
            categoryName: $categoryName !== '' ? $categoryName : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->kind->value,
            'name' => $this->name,
            'slug' => $this->slug,
            'package_id' => $this->packageId,
            'summary' => $this->summary,
            'license' => $this->license,
            'version' => $this->version,
            'changelog' => $this->changelog,
            'published_at' => $this->publishedAt,
            'url' => $this->url,
            'download_url' => $this->downloadUrl,
            'archive_sha256' => $this->archiveSha256,
            'archive_bytes' => $this->archiveBytes,
            'author' => [
                'name' => $this->authorName,
                'slug' => $this->authorSlug,
                'url' => $this->authorUrl,
            ],
            'image_url' => $this->imageUrl,
            'downloads' => $this->downloads,
            'rating_average' => $this->ratingAverage,
            'rating_count' => $this->ratingCount,
            'category' => $this->categoryName === null ? null : [
                'name' => $this->categoryName,
            ],
        ];
    }

    public function hasRating(): bool
    {
        return $this->ratingCount > 0 && $this->ratingAverage !== null;
    }

    /**
     * @return list<'full'|'empty'>
     */
    public function ratingStarStates(): array
    {
        $filled = (int) round(max(0.0, min(5.0, (float) $this->ratingAverage)));

        return array_map(
            static fn (int $index): string => $index <= $filled ? 'full' : 'empty',
            range(1, 5),
        );
    }

    public function formattedRatingAverage(): string
    {
        if ($this->ratingAverage === null) {
            return '';
        }

        $formatted = Number::format($this->ratingAverage, precision: 1);

        return is_string($formatted) ? $formatted : (string) $this->ratingAverage;
    }

    public function formattedDownloadCount(): ?string
    {
        if ($this->downloads < 1) {
            return null;
        }

        if ($this->downloads < 1000) {
            $formatted = Number::format($this->downloads);

            return is_string($formatted) ? $formatted : (string) $this->downloads;
        }

        return Number::abbreviate($this->downloads, precision: 1);
    }

    public function lastPublishedForHumans(): ?string
    {
        $publishedAt = $this->lastPublishedAt();

        return $publishedAt?->diffForHumans();
    }

    public function lastPublishedAt(): ?Carbon
    {
        if (! is_string($this->publishedAt) || trim($this->publishedAt) === '') {
            return null;
        }

        try {
            return Carbon::parse($this->publishedAt);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    private static function optionalRatingAverage(mixed $value, int $ratingCount): ?float
    {
        if ($ratingCount < 1 || $value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max(0.0, min(5.0, (float) $value));
    }

    private static function optionalHttpUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || ! preg_match('#^https?://#i', $value)) {
            return null;
        }

        return $value;
    }
}
