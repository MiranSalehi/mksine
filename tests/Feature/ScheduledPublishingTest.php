<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('mksine.page_cache.enabled', false);
    $this->author = User::factory()->create();
});

function scheduledPost(string $slug, ?CarbonInterface $publishedAt, string $status = 'published'): Post
{
    return Post::query()->create([
        'title' => ucfirst($slug),
        'slug' => $slug,
        'content' => '<p>Body</p>',
        'status' => $status,
        'published_at' => $publishedAt,
        'author_id' => test()->author->getKey(),
    ]);
}

test('the published scope hides posts dated in the future', function (): void {
    scheduledPost('live-post', now()->subHour());
    scheduledPost('undated-post', null);
    scheduledPost('future-post', now()->addDay());
    scheduledPost('draft-post', now()->subHour(), 'draft');

    expect(Post::query()->published()->pluck('slug')->sort()->values()->all())
        ->toBe(['live-post', 'undated-post']);
});

test('a future post is not served until its publish time', function (): void {
    $post = scheduledPost('tomorrow-post', now()->addDay());

    $this->get(route('posts.show', $post->slug))->assertNotFound();
    $this->get(route('posts.index'))->assertOk()->assertDontSee('Tomorrow-post');

    $this->travel(2)->days();

    $this->get(route('posts.show', $post->slug))->assertOk();
});

test('category post lists use the same rule through the pivot', function (): void {
    $category = Category::query()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);
    $category->posts()->attach([
        scheduledPost('visible-news', now()->subHour())->id,
        scheduledPost('future-news', now()->addHour())->id,
    ]);

    expect($category->posts()->published()->pluck('slug')->all())->toBe(['visible-news']);
});

test('scheduled pages go live by the clock without a cron job', function (): void {
    Page::factory()->create(['slug' => 'launch', 'status' => 'scheduled', 'published_at' => now()->addHour()]);
    Page::factory()->create(['slug' => 'scheduled-without-date', 'status' => 'scheduled', 'published_at' => null]);

    expect(Page::query()->published()->pluck('slug')->all())->toBe([]);

    $this->travel(2)->hours();

    expect(Page::query()->published()->pluck('slug')->all())->toBe(['launch']);
});
