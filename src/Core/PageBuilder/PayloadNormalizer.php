<?php

namespace Miran\Mksine\Core\PageBuilder;

final class PayloadNormalizer
{
    public function __construct(private ComponentRegistry $registry) {}

    /**
     * Accept a root block list. Unknown types are dropped. Child buckets stay `{id, items}`.
     *
     * @param  array<int, mixed>  $blocks
     * @return list<array<string, mixed>>
     */
    public function normalize(array $blocks, bool $regenerateIds = false, ?int $ownerSectionId = null): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $node = $this->normalizeBlock($block, $regenerateIds, $ownerSectionId);
            if ($node !== null) {
                $normalized[] = $node;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    public function normalizeBlock(array $block, bool $regenerateIds = false, ?int $ownerSectionId = null): ?array
    {
        $type = $block['type'] ?? null;
        if (! is_string($type) || ! $this->registry->has($type)) {
            return null;
        }

        $class = $this->registry->get($type);
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        if ($type === 'section_ref' && $ownerSectionId !== null && (int) ($data['section_id'] ?? 0) === $ownerSectionId) {
            return null;
        }

        $children = null;
        if ($class !== null && $class::supportsChildren()) {
            $rawChildren = is_array($block['children'] ?? null) ? $block['children'] : [];
            $children = $this->normalizeColumns($rawChildren, $regenerateIds, $ownerSectionId);
        }

        return [
            'id' => $this->blockId($block['id'] ?? null, $regenerateIds),
            'type' => $type,
            'data' => $this->normalizeData($type, $data),
            'children' => $children,
            'presentation' => BlockPresentation::normalize(
                is_array($block['presentation'] ?? null) ? $block['presentation'] : [],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeData(string $type, array $data): array
    {
        if (! $this->registry->has($type)) {
            return $data;
        }

        return $this->registry->validateComponent($type, $data);
    }

    /**
     * @param  array<int, mixed>  $children
     * @return list<array{id: string, items: list<array<string, mixed>>}>
     */
    private function normalizeColumns(array $children, bool $regenerateIds, ?int $ownerSectionId = null): array
    {
        $buckets = [];

        foreach ($children as $column) {
            if (! is_array($column) || ! isset($column['items']) || ! is_array($column['items'])) {
                continue;
            }

            $items = [];
            foreach ($column['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $node = $this->normalizeBlock($item, $regenerateIds, $ownerSectionId);
                if ($node !== null) {
                    $items[] = $node;
                }
            }

            $columnId = $column['id'] ?? null;
            $buckets[] = [
                'id' => ($regenerateIds || ! is_string($columnId) || $columnId === '')
                    ? uniqid('col_')
                    : $columnId,
                'items' => $items,
            ];
        }

        return $buckets;
    }

    private function blockId(mixed $id, bool $regenerateIds): string
    {
        if ($regenerateIds || ! is_string($id) || $id === '') {
            return uniqid('block_');
        }

        return $id;
    }
}
