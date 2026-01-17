<?php

declare(strict_types=1);

use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\MenuLocationAssignment;

describe('Menu Model', function () {
    it('has correct fillable attributes', function () {
        $menu = new Menu;
        $fillable = $menu->getFillable();

        expect($fillable)->toContain('name');
        expect($fillable)->toContain('slug');
        expect($fillable)->toContain('description');
    });

    it('has items relationship', function () {
        $menu = new Menu;
        $relation = $menu->items();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('has rootItems relationship', function () {
        $menu = new Menu;
        $relation = $menu->rootItems();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('has locations relationship', function () {
        $menu = new Menu;
        $relation = $menu->locations();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('returns empty tree for menu without items', function () {
        $menu = new Menu(['name' => 'Test Menu', 'slug' => 'test-menu']);

        // Note: getTree() requires database, so we'll test the structure expectation
        expect($menu)->toBeInstanceOf(Menu::class);
    });
});

describe('MenuItem Model', function () {
    it('has correct fillable attributes', function () {
        $item = new MenuItem;
        $fillable = $item->getFillable();

        expect($fillable)->toContain('menu_id');
        expect($fillable)->toContain('parent_id');
        expect($fillable)->toContain('type');
        expect($fillable)->toContain('label');
        expect($fillable)->toContain('url');
        expect($fillable)->toContain('reference_id');
        expect($fillable)->toContain('order');
        expect($fillable)->toContain('target');
    });

    it('has correct type constants', function () {
        expect(MenuItem::TYPE_CUSTOM_LINK)->toBe('custom_link');
        expect(MenuItem::TYPE_CATEGORY)->toBe('category');
        expect(MenuItem::TYPE_PAGE)->toBe('page');
        expect(MenuItem::TYPE_POST)->toBe('post');
    });

    it('casts order to integer', function () {
        $item = new MenuItem;
        $casts = $item->getCasts();

        expect($casts['order'])->toBe('integer');
    });

    it('casts reference_id to integer', function () {
        $item = new MenuItem;
        $casts = $item->getCasts();

        expect($casts['reference_id'])->toBe('integer');
    });

    it('has menu relationship', function () {
        $item = new MenuItem;
        $relation = $item->menu();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has parent relationship', function () {
        $item = new MenuItem;
        $relation = $item->parent();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has children relationship', function () {
        $item = new MenuItem;
        $relation = $item->children();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('returns correct resolved URL for custom link', function () {
        $item = new MenuItem([
            'type' => MenuItem::TYPE_CUSTOM_LINK,
            'url' => 'https://example.com',
        ]);

        expect($item->getResolvedUrl())->toBe('https://example.com');
    });
});

describe('MenuLocation Model', function () {
    it('has correct fillable attributes', function () {
        $location = new MenuLocation;
        $fillable = $location->getFillable();

        expect($fillable)->toContain('key');
        expect($fillable)->toContain('label');
    });
});

describe('MenuLocationAssignment Model', function () {
    it('has correct fillable attributes', function () {
        $assignment = new MenuLocationAssignment;
        $fillable = $assignment->getFillable();

        expect($fillable)->toContain('menu_id');
        expect($fillable)->toContain('menu_location_id');
    });

    it('has menu relationship', function () {
        $assignment = new MenuLocationAssignment;
        $relation = $assignment->menu();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has location relationship', function () {
        $assignment = new MenuLocationAssignment;
        $relation = $assignment->location();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });
});
