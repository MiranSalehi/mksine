<?php

namespace Miran\Mksine\Core\Theme;

/**
 * Data Transfer Object representing theme metadata.
 *
 * Theme Structure:
 * - src/     : Development source files (CSS, JS with Tailwind/modern tooling)
 * - dist/    : Production-ready compiled assets
 * - views/   : Blade templates
 *
 * In production, only dist/ files are served. No Node.js or build tools required.
 */
final readonly class ThemeData
{
    public function __construct(
        public string $identifier,
        public string $name,
        public string $version,
        public string $path,
        public string $source, // 'package' or 'project'
        public ?string $author = null,
        public ?string $description = null,
        public ?string $screenshot = null,
        public array $assets = [],
    ) {}

    /**
     * Rehydrate from a cached array payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            identifier: (string) ($data['identifier'] ?? ''),
            name: (string) ($data['name'] ?? $data['identifier'] ?? ''),
            version: (string) ($data['version'] ?? '1.0.0'),
            path: (string) ($data['path'] ?? ''),
            source: (string) ($data['source'] ?? 'project'),
            author: isset($data['author']) ? (string) $data['author'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            screenshot: isset($data['screenshot']) ? (string) $data['screenshot'] : null,
            assets: is_array($data['assets'] ?? null) ? $data['assets'] : [],
        );
    }

    /**
     * Create ThemeData from theme.json content.
     */
    public static function fromJson(array $json, string $identifier, string $path, string $source): self
    {
        return new self(
            identifier: $identifier,
            name: $json['name'] ?? $identifier,
            version: $json['version'] ?? '1.0.0',
            path: $path,
            source: $source,
            author: $json['author'] ?? null,
            description: $json['description'] ?? null,
            screenshot: $json['screenshot'] ?? null,
            assets: $json['assets'] ?? [],
        );
    }

    /**
     * Check if this is a package theme.
     */
    public function isPackageTheme(): bool
    {
        return $this->source === 'package';
    }

    /**
     * Check if this is a project theme.
     */
    public function isProjectTheme(): bool
    {
        return $this->source === 'project';
    }

    /**
     * Get CSS asset paths (from dist/).
     */
    public function getCssAssets(): array
    {
        return $this->assets['css'] ?? [];
    }

    /**
     * Get JS asset paths (from dist/).
     */
    public function getJsAssets(): array
    {
        return $this->assets['js'] ?? [];
    }

    /**
     * Check if theme has compiled assets.
     */
    public function hasAssets(): bool
    {
        return ! empty($this->getCssAssets()) || ! empty($this->getJsAssets());
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'version' => $this->version,
            'path' => $this->path,
            'source' => $this->source,
            'author' => $this->author,
            'description' => $this->description,
            'screenshot' => $this->screenshot,
            'assets' => $this->assets,
        ];
    }
}
