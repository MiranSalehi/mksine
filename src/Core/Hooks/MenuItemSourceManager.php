<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Contracts\MenuItemSourceInterface;

/**
 * Manager for menu item sources.
 *
 * Allows plugins to register custom item sources that
 * appear in the Menu Builder UI.
 */
class MenuItemSourceManager
{
    /**
     * @var array<string, MenuItemSourceInterface>
     */
    private array $sources = [];

    /**
     * Register a new item source.
     */
    public function register(string $key, MenuItemSourceInterface $source): void
    {
        $this->sources[$key] = $source;
    }

    /**
     * Get all registered sources.
     *
     * @return array<string, MenuItemSourceInterface>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Get a specific source by key.
     */
    public function getSource(string $key): ?MenuItemSourceInterface
    {
        return $this->sources[$key] ?? null;
    }

    /**
     * Check if a source is registered.
     */
    public function hasSource(string $key): bool
    {
        return isset($this->sources[$key]);
    }

    /**
     * Remove a source.
     */
    public function unregister(string $key): void
    {
        unset($this->sources[$key]);
    }

    /**
     * Get source keys.
     *
     * @return array<string>
     */
    public function getSourceKeys(): array
    {
        return array_keys($this->sources);
    }
}
