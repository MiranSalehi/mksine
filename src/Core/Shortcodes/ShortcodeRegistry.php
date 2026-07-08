<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Shortcodes;

use Miran\Mksine\Core\Hooks\Hooks;

/**
 * @phpstan-type ShortcodeHandlerEntry array{priority: int, handler: callable|string}
 */
final class ShortcodeRegistry
{
    public const ADMIN_CATALOG_FILTER = 'mksine.shortcodes.admin_catalog';

    /** @var array<string, list<ShortcodeHandlerEntry>> */
    private array $handlers = [];

    /** @var array<string, ShortcodeCatalogEntry> */
    private array $catalog = [];

    /** @var array<string, true> */
    private array $livewireTags = [];

    private int $registryVersion = 0;

    public function add(
        string $tag,
        callable|string $handler,
        int $priority = 10,
        bool $livewire = false,
        ?ShortcodeCatalogEntry $catalog = null,
    ): void {
        $tag = $this->normalizeTag($tag);

        if ($tag === '') {
            return;
        }

        if (! isset($this->handlers[$tag])) {
            $this->handlers[$tag] = [];
        }

        $this->handlers[$tag][] = [
            'priority' => $priority,
            'handler' => $handler,
        ];

        usort(
            $this->handlers[$tag],
            static fn (array $a, array $b): int => $a['priority'] <=> $b['priority'],
        );

        if ($livewire) {
            $this->livewireTags[$tag] = true;
        }

        if ($catalog !== null) {
            $this->catalog[$tag] = $catalog;
        } elseif (! isset($this->catalog[$tag])) {
            $this->catalog[$tag] = new ShortcodeCatalogEntry(
                tag: $tag,
                label: $tag,
                example: '['.$tag.']',
            );
        }

        $this->registryVersion++;
    }

    public function has(string $tag): bool
    {
        $tag = $this->normalizeTag($tag);

        return $tag !== '' && isset($this->handlers[$tag]) && $this->handlers[$tag] !== [];
    }

    public function isLivewireTag(string $tag): bool
    {
        $tag = $this->normalizeTag($tag);

        return $tag !== '' && isset($this->livewireTags[$tag]);
    }

    public function registryVersion(): int
    {
        return $this->registryVersion;
    }

    /**
     * @return list<ShortcodeHandlerEntry>
     */
    public function handlersFor(string $tag): array
    {
        $tag = $this->normalizeTag($tag);

        return $this->handlers[$tag] ?? [];
    }

    /**
     * @return list<string>
     */
    public function registeredTags(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * @return list<array{tag: string, label: string, description: string, example: string, selfClosing: bool}>
     */
    public function adminCatalog(): array
    {
        $entries = array_values($this->catalog);

        /** @var list<ShortcodeCatalogEntry> $filtered */
        $filtered = Hooks::filter(self::ADMIN_CATALOG_FILTER, $entries);

        return array_map(
            static fn (ShortcodeCatalogEntry $entry): array => $entry->toArray(),
            $filtered,
        );
    }

    public function contentContainsLivewireTag(string $content): bool
    {
        foreach ($this->livewireTags as $tag => $_) {
            if (str_contains($content, '['.$tag) || str_contains($content, '[/'.$tag.']')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTag(string $tag): string
    {
        $tag = strtolower(trim($tag));

        if ($tag === '' || ! preg_match('/^[a-z0-9_]+$/', $tag)) {
            return '';
        }

        return $tag;
    }
}
