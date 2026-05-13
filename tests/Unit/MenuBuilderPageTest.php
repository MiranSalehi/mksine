<?php

declare(strict_types=1);

use Miran\Mksine\Filament\Pages\MenuBuilder;

/**
 * Tests for the in-memory tree manipulation methods (indentInTree / outdentInTree).
 * These are private, so we invoke them via Reflection. No DB required.
 */
function menuBuilderReflect(string $method, MenuBuilder $page, mixed ...$args): mixed
{
    $ref = new ReflectionMethod(MenuBuilder::class, $method);
    $ref->setAccessible(true);

    return $ref->invoke($page, ...$args);
}

function makeTree(): array
{
    return [
        ['id' => 1, 'label' => 'Home', 'children' => []],
        ['id' => 2, 'label' => 'About', 'children' => [
            ['id' => 21, 'label' => 'Team', 'children' => []],
            ['id' => 22, 'label' => 'Story', 'children' => []],
        ]],
        ['id' => 3, 'label' => 'Contact', 'children' => []],
    ];
}

describe('MenuBuilder indentInTree', function () {
    it('moves second root item under first root item', function () {
        $page = new MenuBuilder;
        $tree = makeTree();

        $result = menuBuilderReflect('indentInTree', $page, $tree, 2);

        // 'About' should now be a child of 'Home'
        expect($result)->toHaveCount(2); // Home (with About) + Contact
        expect($result[0]['id'])->toBe(1);
        expect($result[0]['children'])->toHaveCount(1);
        expect($result[0]['children'][0]['id'])->toBe(2);
        expect($result[1]['id'])->toBe(3);
    });

    it('does nothing when target is the first item at root level', function () {
        $page = new MenuBuilder;
        $tree = makeTree();

        $result = menuBuilderReflect('indentInTree', $page, $tree, 1);

        // Tree unchanged
        expect($result)->toHaveCount(3);
        expect($result[0]['id'])->toBe(1);
    });

    it('nests a child item under its previous sibling', function () {
        $page = new MenuBuilder;
        $tree = makeTree();

        // Indent 'Story' (22) — should become child of 'Team' (21)
        $result = menuBuilderReflect('indentInTree', $page, $tree, 22);

        $about = collect($result)->firstWhere('id', 2);
        expect($about['children'])->toHaveCount(1);
        expect($about['children'][0]['id'])->toBe(21);
        expect($about['children'][0]['children'][0]['id'])->toBe(22);
    });

    it('preserves children of the item being indented', function () {
        $page = new MenuBuilder;
        $tree = [
            ['id' => 1, 'label' => 'A', 'children' => []],
            ['id' => 2, 'label' => 'B', 'children' => [
                ['id' => 3, 'label' => 'C', 'children' => []],
            ]],
        ];

        $result = menuBuilderReflect('indentInTree', $page, $tree, 2);

        expect($result)->toHaveCount(1);
        $a = $result[0];
        expect($a['id'])->toBe(1);
        expect($a['children'][0]['id'])->toBe(2);
        expect($a['children'][0]['children'][0]['id'])->toBe(3);
    });
});

describe('MenuBuilder outdentInTree', function () {
    it('moves a nested item to root level after its parent', function () {
        $page = new MenuBuilder;
        $tree = makeTree();

        [$result] = menuBuilderReflect('outdentInTree', $page, $tree, 21, null, null);

        // 'Team' (21) should now be at root between 'About' and 'Contact'
        $ids = array_column($result, 'id');
        expect($ids)->toBe([1, 2, 21, 3]);
        $about = collect($result)->firstWhere('id', 2);
        expect($about['children'])->toHaveCount(1);
        expect($about['children'][0]['id'])->toBe(22);
    });

    it('does nothing when item is already at root level', function () {
        $page = new MenuBuilder;
        $tree = makeTree();

        [$result] = menuBuilderReflect('outdentInTree', $page, $tree, 3, null, null);

        expect(array_column($result, 'id'))->toBe([1, 2, 3]);
    });

    it('preserves the outdented item\'s own children', function () {
        $page = new MenuBuilder;
        $tree = [
            ['id' => 1, 'label' => 'A', 'children' => [
                ['id' => 2, 'label' => 'B', 'children' => [
                    ['id' => 3, 'label' => 'C', 'children' => []],
                ]],
            ]],
        ];

        [$result] = menuBuilderReflect('outdentInTree', $page, $tree, 2, null, null);

        $ids = array_column($result, 'id');
        expect($ids)->toBe([1, 2]);
        $b = collect($result)->firstWhere('id', 2);
        expect($b['children'])->toHaveCount(1);
        expect($b['children'][0]['id'])->toBe(3);
    });
});

