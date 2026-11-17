<?php

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Models\Media;

/**
 * Request-scoped media lookup for a builder tree.
 * warm() loads every referenced id in one query. find() caches a single id when a theme did not warm.
 */
final class BuilderMediaIndex
{
    /**
     * @var list<string>
     */
    private const ID_KEYS = ['image', 'background_image', 'author_image', 'logo', 'illustration'];

    /**
     * @var array<int, Media|null>
     */
    private array $loaded = [];

    /**
     * @param  array<int, mixed>  $blocks
     */
    public function warm(array $blocks): void
    {
        $ids = [];
        $this->collectIds($blocks, $ids);

        // Merge, never reset: a section reference renders its own payload (and warms) in the
        // middle of the outer page, whose images must stay resolvable afterwards.
        $missing = array_values(array_diff(array_unique($ids), array_keys($this->loaded)));

        if ($missing !== []) {
            $rows = Media::query()->whereIn('id', $missing)->get()->keyBy(fn (Media $media): int => (int) $media->getKey());

            foreach ($missing as $id) {
                $this->loaded[$id] = $rows->get($id);
            }
        }
    }

    public function find(mixed $id): ?Media
    {
        $id = $this->numericId($id);
        if ($id === null) {
            return null;
        }

        if (! array_key_exists($id, $this->loaded)) {
            $this->loaded[$id] = Media::query()->find($id);
        }

        return $this->loaded[$id];
    }

    /**
     * @param  array<int|string, mixed>  $nodes
     * @param  array<int, int>  $ids
     */
    private function collectIds(array $nodes, array &$ids): void
    {
        foreach ($nodes as $key => $value) {
            if (is_string($key) && in_array($key, self::ID_KEYS, true)) {
                $id = $this->numericId($value);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }

            if (is_array($value)) {
                $this->collectIds($value, $ids);
            }
        }
    }

    private function numericId(mixed $id): ?int
    {
        if (! is_numeric($id)) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }
}
