<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

/**
 * In-memory content type definition (CPT). Registered by plugins into {@see ContentTypeRegistry}.
 */
final readonly class ContentType
{
    public function __construct(
        public string $key,
        public string $singularLabel,
        public string $pluralLabel,
        public bool $hasArchive = true,
        public bool $public = true,
        public bool $hasTags = true,
        public bool $hasAdmin = true,
        public string $archiveUri = '',
        public string $singleUri = '',
        public string $icon = 'heroicon-o-rectangle-stack',
    ) {}

    public function archivePath(): string
    {
        $uri = $this->archiveUri !== '' ? $this->archiveUri : '/'.$this->key;

        return self::normalizePath($uri);
    }

    public function singlePattern(): string
    {
        $uri = $this->singleUri !== '' ? $this->singleUri : $this->archivePath().'/{slug}';

        return self::normalizePath($uri);
    }

    public static function normalizePath(string $uri): string
    {
        $path = '/'.ltrim($uri, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }
}
