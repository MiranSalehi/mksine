<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

use InvalidArgumentException;

/**
 * Runtime registry of custom content types. Core Post/Page stay on their own tables.
 */
final class ContentTypeRegistry
{
    /**
     * Keys that must never be registered as a CPT (core routes, permalinks, taxonomies).
     *
     * @var list<string>
     */
    public const array RESERVED = [
        'post',
        'page',
        'posts',
        'pages',
        'category',
        'categories',
        'tag',
        'tags',
        'author',
        'authors',
        'user',
        'users',
        'media',
        'comment',
        'comments',
        'menu',
        'menus',
        'home',
        'admin',
        'api',
        'livewire',
        'storage',
        'filament',
        'login',
        'register',
        'entry',
        'entries',
    ];

    /**
     * @var array<string, ContentType>
     */
    private array $types = [];

    public function register(ContentType $type): void
    {
        $key = $type->key;

        if ($key === '' || ! preg_match('/^[a-z][a-z0-9_\-]*$/', $key)) {
            throw new InvalidArgumentException('Content type key must be lowercase alphanumeric with hyphens/underscores.');
        }

        if (self::isReserved($key)) {
            throw new InvalidArgumentException("Content type key [{$key}] is reserved by MKSine core.");
        }

        if (isset($this->types[$key])) {
            throw new InvalidArgumentException("Content type [{$key}] is already registered.");
        }

        $this->types[$key] = $type;
    }

    public static function isReserved(string $key): bool
    {
        return in_array($key, self::RESERVED, true);
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    public function get(string $key): ContentType
    {
        if (! isset($this->types[$key])) {
            throw new InvalidArgumentException("Unknown content type [{$key}].");
        }

        return $this->types[$key];
    }

    public function find(string $key): ?ContentType
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return array<string, ContentType>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * @return list<ContentType>
     */
    public function publicTypes(): array
    {
        return array_values(array_filter(
            $this->types,
            fn (ContentType $type): bool => $type->public,
        ));
    }

    /**
     * @return list<ContentType>
     */
    public function adminTypes(): array
    {
        return array_values(array_filter(
            $this->types,
            fn (ContentType $type): bool => $type->hasAdmin,
        ));
    }

    public function flush(): void
    {
        $this->types = [];
    }
}
