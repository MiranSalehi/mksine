<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Core\Theme\ThemeRegistry;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Tests\Fixtures\Frontend\ThemeHomeOverride;

require_once __DIR__ . '/../Fixtures/Frontend/ThemeHomeOverride.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->author = User::factory()->create(['name' => 'Sara']);
});

function storefrontPost(array $attributes = []): Post
{
    return Post::factory()->create(array_merge([
        'author_id' => test()->author->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
    ], $attributes));
}

test('the home page renders as plain blade without the livewire runtime', function (): void {
    $response = $this->get(route('home'))->assertOk();

    expect($response->getContent())
        ->toContain('</html>')
        ->not->toContain('livewire.js')
        ->not->toContain('wire:snapshot');
});

test('a post renders its content and keeps comments as a livewire island', function (): void {
    $post = storefrontPost(['title' => 'Speed matters', 'slug' => 'speed-matters', 'content' => '<p>Fast pages</p>']);

    $html = $this->get(route('posts.show', $post->slug))
        ->assertOk()
        ->assertSee('Speed matters')
        ->assertSee('Fast pages', escape: false)
        ->getContent();

    expect($html)
        ->toContain('wire:snapshot')
        ->toContain('livewire.js');

    $this->get(route('posts.show', 'missing-post'))->assertNotFound();
});

test('post lists paginate with plain links that crawlers can follow', function (): void {
    foreach (range(1, 14) as $index) {
        storefrontPost(['title' => 'Post number ' . $index, 'slug' => 'post-number-' . $index, 'published_at' => now()->subMinutes($index)]);
    }

    $first = $this->get(route('posts.index'))->assertOk()->assertSee('Post number 1')->assertDontSee('Post number 14');

    expect($first->getContent())
        ->toContain('?page=2')
        ->not->toContain('wire:click');

    $this->get(route('posts.index', ['page' => 2]))->assertOk()->assertSee('Post number 14')->assertDontSee('Post number 1<');
});

test('the static front page from settings is rendered on the home route', function (): void {
    $page = Page::factory()->create([
        'title' => 'Welcome home',
        'slug' => 'welcome',
        'type' => 'simple',
        'status' => 'published',
        'content' => '<p>Front page body</p>',
        'published_at' => now()->subDay(),
    ]);
    Setting::query()->create(['key' => 'front_page_id', 'value' => (string) $page->id]);

    $this->get(route('home'))->assertOk()->assertSee('Front page body', escape: false);
    $this->get(route('pages.show', 'welcome'))->assertOk()->assertSee('Welcome home');
});

test('a theme livewire override still renders through livewire', function (): void {
    Livewire::component('ztest-theme-home', ThemeHomeOverride::class);
    app(ThemeRegistry::class)->registerOverride('home', ThemeHomeOverride::class);

    $this->get(route('home'))->assertOk()->assertSee('theme livewire home');
});

test('the previous full-page livewire rendering can be switched back on', function (): void {
    config(['mksine.frontend.livewire_pages' => true]);

    expect($this->get(route('home'))->assertOk()->getContent())->toContain('livewire.js');
});
