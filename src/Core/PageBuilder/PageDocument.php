<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Models\Page;

/**
 * Framework-neutral page document. Frontends render `blocks`; they do not need Blade.
 */
final class PageDocument
{
    public const SCHEMA_VERSION = 1;

    public function __construct(
        private PayloadNormalizer $normalizer,
        private SectionBlocks $sections,
        private ComponentRegistry $registry,
    ) {}

    /**
     * @return array{schema_version: int, blocks: list<array{type: string, name: string, category: string, supports_children: bool, defaults: array<string, mixed>}>}
     */
    public function catalog(): array
    {
        $blocks = [];

        foreach ($this->registry->all() as $class) {
            $blocks[] = [
                'type' => $class::getType(),
                'name' => $class::getName(),
                'category' => $class::getCategory(),
                'supports_children' => $class::supportsChildren(),
                'defaults' => $class::getDefaultData(),
            ];
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'blocks' => $blocks,
        ];
    }

    /**
     * @return array{schema_version: int, page: array<string, mixed>}
     */
    public function forPage(Page $page): array
    {
        $payload = is_array($page->builder_payload) ? $page->builder_payload : [];

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'page' => [
                'id' => $page->getKey(),
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => $page->type,
                'show_page_header' => (bool) ($page->show_page_header ?? true),
                'builder_content_width' => $page->builder_content_width ?: 'contained',
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'content' => $page->usesBuilder() ? null : $page->content,
                'blocks' => $page->usesBuilder() ? $this->blocks($payload) : [],
            ],
        ];
    }

    /**
     * Saved sections are expanded. A missing or cyclic section is omitted, matching the public renderer.
     *
     * @param  array<int, mixed>  $payload
     * @param  list<int>  $visited
     * @return list<array<string, mixed>>
     */
    public function blocks(array $payload, array $visited = []): array
    {
        $expanded = [];

        foreach ($this->normalizer->normalize($payload) as $block) {
            foreach ($this->expandBlock($block, $visited) as $node) {
                $expanded[] = $node;
            }
        }

        return $expanded;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  list<int>  $visited
     * @return list<array<string, mixed>>
     */
    private function expandBlock(array $block, array $visited): array
    {
        if (($block['type'] ?? '') === 'section_ref') {
            $sectionId = (int) ($block['data']['section_id'] ?? 0);
            $resolved = $this->sections->resolve($sectionId, $visited);
            if ($resolved['status'] !== 'ok') {
                return [];
            }

            return $this->blocks($resolved['blocks'], array_merge($visited, [$sectionId]));
        }

        if (is_array($block['children'] ?? null)) {
            foreach ($block['children'] as $index => $column) {
                if (! is_array($column)) {
                    continue;
                }

                $items = is_array($column['items'] ?? null) ? $column['items'] : [];
                $column['items'] = $this->blocks($items, $visited);
                $block['children'][$index] = $column;
            }
        }

        return [$block];
    }
}
