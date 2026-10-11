<?php

namespace Miran\Mksine\Core\PageBuilder;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderRevision;

/**
 * Durable builder history. One row per saved payload, not per editor drag.
 */
final class PageBuilderRevisionStore
{
    public const KEEP = 20;

    public function record(Page $page, ?int $userId = null): void
    {
        if (! $page->usesBuilder() || $page->getKey() === null) {
            return;
        }

        $payload = is_array($page->builder_payload) ? $page->builder_payload : [];

        $latest = $this->latest($page);
        if ($latest instanceof PageBuilderRevision && $this->same($latest->builder_payload, $payload)) {
            return;
        }

        PageBuilderRevision::query()->create([
            'page_id' => $page->getKey(),
            'builder_payload' => $payload,
            'created_by' => $userId,
        ]);

        $this->prune($page);
    }

    public function restore(Page $page, int $revisionId, ?int $userId = null): PageBuilderRevision
    {
        $revision = PageBuilderRevision::query()
            ->where('page_id', $page->getKey())
            ->whereKey($revisionId)
            ->first();

        if (! $revision instanceof PageBuilderRevision) {
            throw (new ModelNotFoundException)->setModel(PageBuilderRevision::class, [$revisionId]);
        }

        $this->record($page, $userId);

        $page->builder_payload = $revision->builder_payload;
        $page->updated_by = $userId;
        $page->save();

        $this->record($page, $userId);

        return $revision;
    }

    private function latest(Page $page): ?PageBuilderRevision
    {
        $revision = PageBuilderRevision::query()
            ->where('page_id', $page->getKey())
            ->latest('id')
            ->first();

        return $revision instanceof PageBuilderRevision ? $revision : null;
    }

    private function prune(Page $page): void
    {
        $keepIds = PageBuilderRevision::query()
            ->where('page_id', $page->getKey())
            ->latest('id')
            ->limit(self::KEEP)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        PageBuilderRevision::query()
            ->where('page_id', $page->getKey())
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * @param  array<int|string, mixed>|null  $left
     * @param  array<int|string, mixed>  $right
     */
    private function same(?array $left, array $right): bool
    {
        return $this->canonical($left ?? []) === $this->canonical($right);
    }

    /**
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function sortKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortKeys($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    private function canonical(array $value): string
    {
        return json_encode($this->sortKeys($value)) ?: '[]';
    }
}
