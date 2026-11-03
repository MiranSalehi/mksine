<?php

declare(strict_types=1);

use App\Models\User;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Facades\Filament;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    config(['app.locale' => 'fa']);
});

/**
 * Snapshot of the first Livewire component (the panel page itself) in a rendered response.
 */
function panelLocaleFirstSnapshot(TestResponse $response): string
{
    preg_match('/wire:snapshot="([^"]+)"/', (string) $response->getContent(), $matches);

    expect($matches)->toHaveKey(1);

    return html_entity_decode($matches[1], ENT_QUOTES);
}

it('uses APP_LOCALE in the panel for a browser language it does not prefer', function (): void {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get('/admin/login')
        ->assertOk();

    expect(app()->getLocale())->toBe('fa')
        ->and(LanguageSwitch::make()->getUserPreferredLocale())->toBe('fa');
});

it('uses APP_LOCALE in the panel when the browser sends no language', function (): void {
    $this->withHeader('Accept-Language', '')
        ->get('/admin/login')
        ->assertOk();

    expect(app()->getLocale())->toBe('fa')
        ->and(LanguageSwitch::make()->getUserPreferredLocale())->toBe('fa');
});

it('keeps the language picked in the switcher over APP_LOCALE', function (): void {
    $this->withSession(['locale' => 'en'])
        ->get('/admin/login')
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('keeps the remembered switcher cookie over APP_LOCALE', function (): void {
    $this->withCookie('filament_language_switch_locale', 'en')
        ->get('/admin/login')
        ->assertOk();

    expect(LanguageSwitch::make()->getUserPreferredLocale())->toBe('en');
});

it('prefers the locale saved on the user over APP_LOCALE', function (): void {
    $user = new class extends User implements HasLocalePreference
    {
        public function preferredLocale(): string
        {
            return 'en';
        }
    };

    $this->actingAs($user);

    expect(LanguageSwitch::make()->getUserPreferredLocale())->toBe('en');
});

it('leaves the browser language to the package when APP_LOCALE is not available', function (): void {
    config(['app.locale' => 'de']);

    expect(LanguageSwitch::make()->getUserPreferredLocale())->toBeNull();
});

it('applies the switcher language to the panel livewire update requests', function (): void {
    $this->withSession(['locale' => 'en']);

    $snapshot = panelLocaleFirstSnapshot($this->get('/admin/login')->assertOk());

    // The page request already switched to en; start the update from APP_LOCALE again.
    app()->setLocale('fa');

    // Livewire restores the component locale from the snapshot only on hydrate; panel serving
    // callbacks run before that and depend on the persistent middleware.
    $servingLocales = [];
    Filament::serving(function () use (&$servingLocales): void {
        $servingLocales[] = app()->getLocale();
    });

    $this->withHeader('X-Livewire', 'true')
        ->postJson(Livewire::getUpdateUri(), [
            'components' => [
                ['snapshot' => $snapshot, 'updates' => [], 'calls' => []],
            ],
        ])
        ->assertOk();

    expect($servingLocales)->toBe(['en']);
});
