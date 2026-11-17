<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Models\Category;

uses(RefreshDatabase::class);

it('returns 404 when the category does not exist', function () {
    $this->get(route('categories.show', ['path' => 'does-not-exist']))
        ->assertNotFound();
});

it('returns 404 when a nested category path does not exist', function () {
    Category::factory()->create(['slug' => 'news', 'is_active' => true]);

    $this->get(route('categories.show', ['path' => 'news/missing-child']))
        ->assertNotFound();

    $this->get(route('categories.show', ['path' => 'missing-parent/missing-child']))
        ->assertNotFound();
});

it('returns 404 when the category is inactive', function () {
    $category = Category::factory()->inactive()->create(['slug' => 'hidden-cat']);

    $this->get(route('categories.show', ['path' => $category->slug]))
        ->assertNotFound();
});

it('renders an existing active category', function () {
    $category = Category::factory()->create([
        'name' => 'Guides',
        'slug' => 'guides',
        'is_active' => true,
    ]);

    $this->get(route('categories.show', ['path' => $category->slug]))
        ->assertOk()
        ->assertSee('Guides', escape: false);
});

it('renders an existing nested category', function () {
    $parent = Category::factory()->create(['slug' => 'news', 'is_active' => true]);
    Category::factory()->childOf($parent)->create([
        'name' => 'Local News',
        'slug' => 'local',
        'is_active' => true,
    ]);

    $this->get(route('categories.show', ['path' => 'news/local']))
        ->assertOk()
        ->assertSee('Local News', escape: false);
});
