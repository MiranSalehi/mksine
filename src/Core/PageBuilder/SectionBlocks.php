<?php

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Models\PageBuilderSection;

/**
 * Resolves a saved section at render time. A cycle or a missing row does not expand.
 */
final class SectionBlocks
{
    /**
     * @param  list<int>  $visited
     * @return array{status: 'ok'|'missing'|'cycle', blocks: list<array<string, mixed>>}
     */
    public function resolve(int $sectionId, array $visited): array
    {
        if ($sectionId < 1) {
            return ['status' => 'missing', 'blocks' => []];
        }

        if (in_array($sectionId, $visited, true)) {
            return ['status' => 'cycle', 'blocks' => []];
        }

        $section = PageBuilderSection::query()->find($sectionId);

        if (! $section instanceof PageBuilderSection || ! is_array($section->builder_payload)) {
            return ['status' => 'missing', 'blocks' => []];
        }

        return [
            'status' => 'ok',
            'blocks' => array_values($section->builder_payload),
        ];
    }
}
