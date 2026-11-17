<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Content\ContentVisibility;
use Miran\Mksine\Core\Hooks\HookFilterRegistry;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Models\Page;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->singleton(HookFilterRegistry::class, fn () => new HookFilterRegistry);

    Page::factory()->create(['title' => 'Public', 'slug' => 'public-doc', 'status' => 'published', 'published_at' => now()->subDay()]);
    Page::factory()->create(['title' => 'Members', 'slug' => 'members-doc', 'status' => 'published', 'published_at' => now()->subDay()]);

    // A membership plugin: "members-doc" needs a signed-in user.
    Hooks::addFilter(ContentVisibility::FILTER_VISIBLE, function (bool $visible, Model $record, string $type, mixed $user): bool {
        return $visible && ($type !== 'page' || $record->getAttribute('slug') !== 'members-doc' || $user !== null);
    });

    Hooks::addFilter(ContentVisibility::FILTER_QUERY, function (Builder $query, string $type, mixed $user): Builder {
        return $type === 'page' && $user === null ? $query->where('slug', '!=', 'members-doc') : $query;
    });
});

test('guests cannot read members only pages from the document api', function (): void {
    $this->getJson('/api/mksine/pages/members-doc')->assertNotFound();

    $slugs = collect($this->getJson('/api/mksine/pages')->assertOk()->json('data'))->pluck('slug');

    expect($slugs)->toContain('public-doc')->not->toContain('members-doc');
});

test('signed in members can read members only pages', function (): void {
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/mksine/pages/members-doc')->assertOk()->assertJsonPath('page.title', 'Members');
});

test('a record level visibility filter alone also protects the document', function (): void {
    $this->app->singleton(HookFilterRegistry::class, fn () => new HookFilterRegistry);
    Hooks::addFilter(ContentVisibility::FILTER_VISIBLE, fn (bool $visible, Model $record, string $type, mixed $user): bool => $user !== null);

    $this->getJson('/api/mksine/pages/public-doc')->assertUnauthorized();

    $this->actingAs(User::factory()->create());
    $this->app->singleton(HookFilterRegistry::class, fn () => new HookFilterRegistry);
    Hooks::addFilter(ContentVisibility::FILTER_VISIBLE, fn (): bool => false);

    $this->getJson('/api/mksine/pages/public-doc')->assertForbidden();
});
