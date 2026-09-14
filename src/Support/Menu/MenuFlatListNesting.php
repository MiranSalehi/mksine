<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Menu;

/**
 * WordPress-style flat-list nesting helpers. Alpine drag in menu-builder.blade.php
 * must match this contract: outdent repositions the subtree after remaining siblings
 * instead of leaving it in place (which would swallow those siblings via depth walk).
 */
final class MenuFlatListNesting
{
    /**
     * @param  array<int, array{id: int, depth: int}>  $rows
     * @param  list<int>  $descendantIds
     * @return array<int, array{id: int, depth: int}>
     */
    public static function repositionAfterOutdent(array $rows, int $draggedId, int $newDepth, array $descendantIds = []): array
    {
        $dragIdx = null;
        foreach ($rows as $i => $row) {
            if ($row['id'] === $draggedId) {
                $dragIdx = $i;
                break;
            }
        }

        if ($dragIdx === null) {
            return $rows;
        }

        $descendantSet = array_fill_keys($descendantIds, true);
        $subtreeIds = [$draggedId];
        $count = count($rows);

        for ($i = $dragIdx + 1; $i < $count; $i++) {
            $id = $rows[$i]['id'];
            if (! isset($descendantSet[$id])) {
                break;
            }
            $subtreeIds[] = $id;
        }

        $subtreeIdSet = array_fill_keys($subtreeIds, true);
        $lastSkipId = null;

        for ($j = $dragIdx + count($subtreeIds); $j < $count; $j++) {
            $id = $rows[$j]['id'];
            if (isset($subtreeIdSet[$id])) {
                continue;
            }

            if ($rows[$j]['depth'] > $newDepth) {
                $lastSkipId = $id;

                continue;
            }

            break;
        }

        if ($lastSkipId === null) {
            return $rows;
        }

        $subtree = [];
        $rest = [];
        foreach ($rows as $row) {
            if (isset($subtreeIdSet[$row['id']])) {
                $subtree[] = $row;
            } else {
                $rest[] = $row;
            }
        }

        $out = [];
        foreach ($rest as $row) {
            $out[] = $row;
            if ($row['id'] === $lastSkipId) {
                foreach ($subtree as $node) {
                    $out[] = $node;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array{id: int, depth: int}>  $rows
     * @return array<int, array{id: int, children: array<int, mixed>}>
     */
    public static function buildTreeFromFlat(array $rows): array
    {
        $root = (object) ['depth' => -1, 'children' => []];
        $stack = [$root];

        foreach ($rows as $row) {
            $depth = $row['depth'];

            while (count($stack) > 1 && $stack[count($stack) - 1]->depth >= $depth) {
                array_pop($stack);
            }

            $parent = $stack[count($stack) - 1];
            if ($depth > $parent->depth + 1) {
                $depth = $parent->depth + 1;
            }

            $node = (object) ['id' => $row['id'], 'depth' => $depth, 'children' => []];
            $parent->children[] = $node;
            $stack[] = $node;
        }

        return array_map(self::nodeToArray(...), $root->children);
    }

    /**
     * @return array{id: int, children: array<int, mixed>}
     */
    private static function nodeToArray(object $node): array
    {
        return [
            'id' => $node->id,
            'children' => array_map(self::nodeToArray(...), $node->children),
        ];
    }
}
