<?php

declare(strict_types=1);

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;
use Miran\Mksine\Filament\Pages\Languages;
use Miran\Mksine\Filament\Pages\Settings\SettingsGeneralPage;
use Miran\Mksine\Filament\Pages\Settings\SettingsMenuPage;
use Miran\Mksine\Filament\Resources\GeoStates\GeoStateResource;

require_once __DIR__ . '/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    $this->actingAs(adminMenuSuperAdmin());
    adminMenuPanel();
});

/**
 * @param  array<NavigationGroup>  $subNavigation
 * @return array<string, array{group: ?string, label: string, url: ?string}>
 */
function adminMenuSubNavigationLinks(array $subNavigation): array
{
    $links = [];

    foreach ($subNavigation as $group) {
        foreach (collect($group->getItems())->all() as $item) {
            /** @var NavigationItem $item */
            $links[$item->getKey()] = [
                'group' => $group->getLabel(),
                'label' => $item->getLabel(),
                'url' => $item->getUrl(),
            ];
        }
    }

    return $links;
}

test('items moved to settings keep their url and appear in the settings sub-navigation', function (): void {
    $languagesUrl = Languages::getUrl();
    $geoStatesUrl = GeoStateResource::getUrl();

    adminMenuStore(['items' => [
        Languages::class => ['placement' => 'settings'],
        GeoStateResource::class => ['placement' => 'settings', 'label' => 'Provinces'],
    ]]);

    $links = adminMenuSubNavigationLinks(
        Livewire::test(SettingsGeneralPage::class)->instance()->getCachedSubNavigation(),
    );

    expect(Languages::getUrl())->toBe($languagesUrl)
        ->and($languagesUrl)->not->toContain('/settings/')
        ->and($links[Languages::class])->toBe([
            'group' => __('mksine::admin_menu.settings_navigation_group'),
            'label' => Languages::getNavigationLabel(),
            'url' => $languagesUrl,
        ])
        ->and($links[GeoStateResource::class]['label'])->toBe('Provinces')
        ->and($links[GeoStateResource::class]['url'])->toBe($geoStatesUrl)
        ->and($links)->toHaveKey(SettingsGeneralPage::class)
        ->and($links)->not->toHaveKey(ConsoleTerminal::class);
});

test('items moved to settings leave the sidebar', function (): void {
    adminMenuStore(['items' => [Languages::class => ['placement' => 'settings']]]);

    adminMenuFreshManager();
    $keys = adminMenuKeys(adminMenuPanel()->getNavigation());

    expect($keys['tools'] ?? [])->not->toContain(Languages::class)
        ->and($keys['tools'] ?? [])->toContain(ConsoleTerminal::class);
});

test('the menu editor page also shows the moved links', function (): void {
    adminMenuStore(['items' => [Languages::class => ['placement' => 'settings']]]);

    $links = adminMenuSubNavigationLinks(
        Livewire::test(SettingsMenuPage::class)->instance()->getCachedSubNavigation(),
    );

    expect($links)->toHaveKey(Languages::class)
        ->and($links)->toHaveKey(SettingsMenuPage::class);
});

test('bookmarked urls of moved and hidden items still open', function (): void {
    adminMenuStore(['items' => [
        Languages::class => ['placement' => 'settings'],
        ConsoleTerminal::class => ['placement' => 'hidden'],
    ]]);

    $this->get(Languages::getUrl())->assertOk();
    $this->get(ConsoleTerminal::getUrl())->assertOk();
});

test('without moved items the settings sub-navigation is unchanged', function (): void {
    $links = adminMenuSubNavigationLinks(
        Livewire::test(SettingsGeneralPage::class)->instance()->getCachedSubNavigation(),
    );

    expect(collect($links)->pluck('group')->unique()->all())
        ->not->toContain(__('mksine::admin_menu.settings_navigation_group'));
});
