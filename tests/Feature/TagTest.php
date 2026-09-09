<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;

uses(RefreshDatabase::class);

it('creates a unique slug and reuses an existing tag by name', function () {
    $first = Tag::findOrCreateFromName('Foo');
    $again = Tag::findOrCreateFromName('Foo');

    expect($first->slug)->toBe('foo')
        ->and($again->is($first))->toBeTrue();
});

it('appends a numeric suffix when the slug is already taken', function () {
    Tag::factory()->create([
        'name' => 'Existing',
        'slug' => 'hello',
        'is_active' => true,
    ]);

    $tag = Tag::findOrCreateFromName('Hello');

    expect($tag->name)->toBe('Hello')
        ->and($tag->slug)->toBe('hello-2');
});

it('attaches the same tag to a post and a page', function () {
    $author = User::factory()->create();
    $tag = Tag::factory()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);
    $post = Post::factory()->published()->forAuthor($author->id)->create();
    $page = Page::factory()->published()->create();

    $post->tags()->attach($tag->id, ['sort_order' => 0]);
    $page->tags()->attach($tag->id, ['sort_order' => 1]);

    expect($post->fresh()->tags)->toHaveCount(1)
        ->and($post->tags->first()->is($tag))->toBeTrue()
        ->and($page->fresh()->tags)->toHaveCount(1)
        ->and($page->tags->first()->is($tag))->toBeTrue()
        ->and($tag->fresh()->posts)->toHaveCount(1)
        ->and($tag->pages)->toHaveCount(1);
});
