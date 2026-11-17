<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Miran\Mksine\Core\Events\Storefront\StorefrontViewed;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\PageCache\PageCache;
use Miran\Mksine\Filament\Pages\Settings\SettingsPerformancePage;
use Miran\Mksine\Http\Middleware\CacheStorefrontPage;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Setting;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['mksine.page_cache.enabled' => true]);

    $this->renders = 0;

    Route::middleware(['web', CacheStorefrontPage::class])->group(function (): void {
        Route::get('/page-cache-test', function () {
            $this->renders++;

            return response('<html><body><meta name="csrf-token" content="' . csrf_token() . '">render ' . $this->renders . '</body></html>');
        });

        Route::get('/page-cache-test/personal', function () {
            PageCache::skip();

            return response('<html><body>cart: 3</body></html>');
        });

        Route::get('/page-cache-test/cookie', fn () => response('<html><body>x</body></html>')->cookie('ref', 'abc'));

        Route::get('/page-cache-test/json', fn () => response()->json(['ok' => true]));
    });
});

test('the cache is off unless enabled', function (): void {
    config(['mksine.page_cache.enabled' => false]);

    $this->get('/page-cache-test')->assertOk()->assertHeaderMissing(PageCache::HEADER);
    $this->get('/page-cache-test')->assertSee('render 2');
});

test('the saved setting overrides the config default', function (): void {
    Setting::query()->create(['key' => PageCache::SETTING_ENABLED, 'value' => '0']);

    $this->get('/page-cache-test')->assertHeaderMissing(PageCache::HEADER);
});

test('a guest page is rendered once and then served without running the route', function (): void {
    $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'MISS')->assertSee('render 1');

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'HIT')->assertSee('render 1');

    expect($this->renders)->toBe(1)
        ->and($queries)->toBe(0);
});

test('every visitor gets their own csrf token from a cached page', function (): void {
    $first = $this->get('/page-cache-test')->getContent();
    $firstToken = session()->token();

    session()->regenerateToken();
    $secondToken = session()->token();

    $second = $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'HIT')->getContent();

    expect($firstToken)->not->toBe($secondToken)
        ->and($first)->toContain($firstToken)
        ->and($second)->toContain($secondToken)
        ->not->toContain($firstToken)
        ->not->toContain('__MKSINE_PAGE_CACHE_CSRF__');
});

test('signed-in users always get a fresh render', function (): void {
    $this->get('/page-cache-test');

    $this->actingAs(User::factory()->create());

    $this->get('/page-cache-test')->assertSee('render 2')->assertHeaderMissing(PageCache::HEADER);
});

test('query strings: tracking parameters are ignored, page varies the key, others skip the cache', function (): void {
    $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'MISS');
    $this->get('/page-cache-test?utm_source=telegram&fbclid=1')->assertHeader(PageCache::HEADER, 'HIT');
    $this->get('/page-cache-test?page=2')->assertHeader(PageCache::HEADER, 'MISS');
    $this->get('/page-cache-test?page=2')->assertHeader(PageCache::HEADER, 'HIT');
    $this->get('/page-cache-test?q=search')->assertHeaderMissing(PageCache::HEADER)->assertSee('render 3');
});

test('pages marked personal, setting cookies or not html are never stored', function (string $uri): void {
    $this->get($uri)->assertHeader(PageCache::HEADER, 'BYPASS');
    $this->get($uri)->assertHeader(PageCache::HEADER, 'BYPASS');
})->with(['/page-cache-test/personal', '/page-cache-test/cookie', '/page-cache-test/json']);

test('flashed session data skips the cache', function (): void {
    $this->get('/page-cache-test');

    $this->withSession(['_flash' => ['old' => ['status'], 'new' => []], 'status' => 'Saved'])
        ->get('/page-cache-test')
        ->assertSee('render 2');
});

test('plugins can veto caching with a filter', function (): void {
    Hooks::addFilter(PageCache::FILTER_SHOULD_CACHE, fn (bool $cache): bool => false);

    $this->get('/page-cache-test')->assertHeaderMissing(PageCache::HEADER);
    $this->get('/page-cache-test')->assertSee('render 2');
});

test('flushing or saving content invalidates every page', function (): void {
    $this->get('/page-cache-test')->assertSee('render 1');
    $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'HIT');

    app()->forgetScopedInstances();
    Category::factory()->create(['slug' => 'flush-me']);

    $this->get('/page-cache-test')->assertHeader(PageCache::HEADER, 'MISS')->assertSee('render 2');

    app()->forgetScopedInstances();
    $this->artisan('mksine:page-cache-clear')->assertSuccessful();

    $this->get('/page-cache-test')->assertSee('render 3');
});

test('storefront pages are cached and their view event is replayed on hits', function (): void {
    $category = Category::factory()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);
    $views = [];
    Hooks::addFilter(StorefrontViewed::FILTER, function (StorefrontViewed $event) use (&$views): StorefrontViewed {
        $views[] = $event->data()->get('content_type');

        return $event;
    });

    $url = route('categories.show', ['path' => $category->slug]);

    $this->get($url)->assertOk()->assertHeader(PageCache::HEADER, 'MISS')->assertSee('Guides', escape: false);
    $this->get($url)->assertOk()->assertHeader(PageCache::HEADER, 'HIT')->assertSee('Guides', escape: false);

    app()->terminate();

    expect($views)->toBe(['category', 'category']);
});

test('super admins switch the cache on and clear it from settings', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web'));
    $this->actingAs($superAdmin);

    config(['mksine.page_cache.enabled' => false]);

    Livewire::test(SettingsPerformancePage::class)
        ->set('data.' . PageCache::SETTING_ENABLED, true)
        ->set('data.' . PageCache::SETTING_TTL_MINUTES, 30)
        ->call('saveData')
        ->assertHasNoErrors()
        ->callAction('clearPageCache')
        ->assertNotified(__('mksine::settings.page_cache.cleared'));

    app()->forgetScopedInstances();

    expect(app(PageCache::class)->isEnabled())->toBeTrue()
        ->and(app(PageCache::class)->ttlSeconds())->toBe(1800);
});
