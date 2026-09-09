<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;

uses(RefreshDatabase::class);

it('returns 404 when the tag does not exist', function () {
    $this->get(route('tags.show', ['slug' => 'missing-tag']))
        ->assertNotFound();
});

it('returns 404 when the tag is inactive', function () {
    $tag = Tag::factory()->inactive()->create([
        'name' => 'Hidden',
        'slug' => 'hidden-tag',
    ]);

    $this->get(route('tags.show', ['slug' => $tag->slug]))
        ->assertNotFound();
});

it('lists only published posts and pages on the tag archive', function () {
    $author = User::factory()->create();
    $tag = Tag::factory()->create([
        'name' => 'Release',
        'slug' => 'release',
        'is_active' => true,
    ]);

    $publishedPost = Post::factory()->published()->forAuthor($author->id)->create([
        'title' => 'Published Tagged Post',
    ]);
    $draftPost = Post::factory()->draft()->forAuthor($author->id)->create([
        'title' => 'Draft Tagged Post',
    ]);
    $publishedPage = Page::factory()->published()->create([
        'title' => 'Published Tagged Page',
    ]);
    $draftPage = Page::factory()->draft()->create([
        'title' => 'Draft Tagged Page',
    ]);

    $tag->posts()->attach([
        $publishedPost->id => ['sort_order' => 0],
        $draftPost->id => ['sort_order' => 1],
    ]);
    $tag->pages()->attach([
        $publishedPage->id => ['sort_order' => 0],
        $draftPage->id => ['sort_order' => 1],
    ]);

    $this->get(route('tags.show', ['slug' => $tag->slug]))
        ->assertOk()
        ->assertSee('Published Tagged Post', escape: false)
        ->assertDontSee('Draft Tagged Post', escape: false)
        ->assertSee('Published Tagged Page', escape: false)
        ->assertDontSee('Draft Tagged Page', escape: false);
});
