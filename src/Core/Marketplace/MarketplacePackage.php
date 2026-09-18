<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

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
        ];
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
