<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Miran\Mksine\Core\Events\Content\ContentSlugChanged;
use Miran\Mksine\Core\Events\Storefront\StorefrontNotFound;
use Miran\Mksine\Core\Events\Storefront\StorefrontViewed;
use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Http\StorefrontRequest;
use Miran\Mksine\Http\StorefrontViewedResponder;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->singleton(HookFilterRegistry::class, fn () => new HookFilterRegistry);
});

test('storefront 404 filter can return a redirect', function (): void {
    Hooks::addFilter(StorefrontNotFound::FILTER, function ($response, Request $request, string $path) {
        expect($path)->toBe('/no-such-mksine-page-xyz');

        return redirect('/redirected-from-404', 301);
    });

    $this->get('/no-such-mksine-page-xyz')
        ->assertRedirect('/redirected-from-404')
        ->assertStatus(301);
});

test('admin 404s are not treated as storefront', function (): void {
    $seen = false;
    Hooks::addFilter(StorefrontNotFound::FILTER, function ($response) use (&$seen) {
        $seen = true;

        return $response;
    });

    $adminRequest = Request::create('/admin/missing-thing', 'GET');
    $this->app->instance('request', $adminRequest);

    expect(StorefrontRequest::isStorefront($adminRequest))->toBeFalse()
        ->and($seen)->toBeFalse();
});

test('slug change on a post dispatches content slug changed', function (): void {
    $captured = null;
    Hooks::addFilter(ContentSlugChanged::FILTER, function (ContentSlugChanged $event) use (&$captured) {
        $captured = $event;

        return $event;
    });

    $author = User::factory()->create();
    $post = Post::factory()->forAuthor($author->id)->create(['slug' => 'old-slug']);
    $post->update(['slug' => 'new-slug']);

    expect($captured)->toBeInstanceOf(ContentSlugChanged::class)
        ->and($captured->data()->get('type'))->toBe('post')
        ->and($captured->data()->get('old'))->toBe('old-slug')
        ->and($captured->data()->get('new'))->toBe('new-slug')
        ->and($captured->data()->get('old_path'))->toBe(Permalink::storefrontPathForContent('post', 'old-slug'))
        ->and($captured->data()->get('new_path'))->toBe(Permalink::storefrontPathForContent('post', 'new-slug'));
});

test('slug change on a page dispatches content slug changed', function (): void {
    $captured = null;
    Hooks::addFilter(ContentSlugChanged::FILTER, function (ContentSlugChanged $event) use (&$captured) {
        $captured = $event;

        return $event;
    });

    $page = Page::factory()->create(['slug' => 'about-old']);
    $page->update(['slug' => 'about-new']);

    expect($captured)->toBeInstanceOf(ContentSlugChanged::class)
        ->and($captured->data()->get('type'))->toBe('page')
        ->and($captured->data()->get('old'))->toBe('about-old')
        ->and($captured->data()->get('new'))->toBe('about-new');
});

test('published post show fires storefront viewed', function (): void {
    $captured = null;
    Hooks::addFilter(StorefrontViewed::FILTER, function (StorefrontViewed $event) use (&$captured) {
        $captured = $event;

        return $event;
    });

    $author = User::factory()->create();
    $post = Post::factory()->forAuthor($author->id)->create([
        'slug' => 'analytics-view-hook',
        'status' => 'published',
    ]);

    $this->get(route('posts.show', $post->slug))->assertSuccessful();

    expect($captured)->toBeInstanceOf(StorefrontViewed::class)
        ->and($captured->data()->get('content_type'))->toBe('post')
        ->and($captured->data()->get('content_id'))->toBe($post->id)
        ->and($captured->data()->get('status'))->toBe('published');
});

test('admin requests do not fire storefront viewed', function (): void {
    $seen = false;
    Hooks::addFilter(StorefrontViewed::FILTER, function ($event) use (&$seen) {
        $seen = true;

        return $event;
    });

    $adminRequest = Request::create('/admin/posts', 'GET');
    $this->app->instance('request', $adminRequest);

    StorefrontViewedResponder::emit(['type' => 'post', 'id' => 1]);

    expect($seen)->toBeFalse();
});

test('unchanged slug does not fire content slug changed', function (): void {
    $fired = false;
    Hooks::addFilter(ContentSlugChanged::FILTER, function ($event) use (&$fired) {
        $fired = true;

        return $event;
    });

    $author = User::factory()->create();
    $post = Post::factory()->forAuthor($author->id)->create(['slug' => 'same-slug', 'title' => 'A']);
    $post->update(['title' => 'B']);

    expect($fired)->toBeFalse();
});

test('content visibility filter can deny a published post show', function (): void {
    Hooks::addFilter(\Miran\Mksine\Core\Content\ContentVisibility::FILTER_VISIBLE, fn () => false);

    $author = User::factory()->create();
    $post = Post::factory()->forAuthor($author->id)->create([
        'slug' => 'members-only-core-hook',
        'status' => 'published',
    ]);

    $this->get(route('posts.show', $post->slug))->assertRedirect();
});
