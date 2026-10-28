<?php

declare(strict_types=1);

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;
use Miran\Mksine\Filament\Pages\Languages;
use Miran\Mksine\Filament\Pages\ManagePlugins;
use Miran\Mksine\Filament\Pages\MksineDashboard;
use Miran\Mksine\Filament\Pages\SystemUpdate;
use Miran\Mksine\Filament\Pages\ThemeManager;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;
use Miran\Mksine\Filament\Resources\Comments\CommentResource;
use Miran\Mksine\Filament\Resources\GeoStates\GeoStateResource;
use Miran\Mksine\Filament\Resources\Media\MediaResource;
use Miran\Mksine\Filament\Resources\MenuLocations\MenuLocationResource;
use Miran\Mksine\Filament\Resources\Menus\MenuResource;
use Miran\Mksine\Filament\Resources\PageBuilderSections\PageBuilderSectionResource;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Filament\Resources\Posts\PostResource;
use Miran\Mksine\Filament\Resources\Tags\TagResource;
use Miran\Mksine\Filament\Resources\Users\UserResource;
use Miran\Mksine\Filament\Support\AdminMenu\MksineNavigationManager;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;

require_once __DIR__ . '/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    $this->actingAs(adminMenuSuperAdmin());
});

test('mksine plugin binds the mksine navigation manager', function (): void {
    expect(adminMenuFreshManager())->toBeInstanceOf(MksineNavigationManager::class)
        ->and(adminMenuFreshManager()->managesPanel())->toBeTrue();
});

test('without overrides the admin menu is exactly what filament builds', function (): void {
    // Keys are not rendered; keyless items get a URL key (covered below), so compare what is shown.
    $filament = adminMenuSnapshot(adminMenuNavigationWith(new NavigationManager), withKeys: false);
    $mksine = adminMenuSnapshot(adminMenuNavigationWith(new MksineNavigationManager), withKeys: false);

    expect($mksine)->toBe($filament)
        ->and($mksine)->not->toBeEmpty();
});

test('items without a key are keyed by url so overrides work in every locale', function (): void {
    adminMenuPanel()->navigationItems([
        NavigationItem::make(fn (): string => __('mksine::admin_menu.title'))
            ->group(AdminNavigationGroup::Tools)
            ->url('/admin/keyless-report?tab=sales'),
        NavigationItem::make('Keyed report')
            ->key('acme.keyed-report')
            ->group(AdminNavigationGroup::Tools)
            ->url('/admin/keyed-report'),
    ]);

    $keys = adminMenuKeys(adminMenuNavigationWith(new MksineNavigationManager));

    expect($keys['tools'])->toContain('url:/admin/keyless-report?tab=sales')
        ->and($keys['tools'])->toContain('acme.keyed-report');

    adminMenuStore(['items' => ['url:/admin/keyless-report?tab=sales' => ['placement' => 'hidden']]]);

    foreach (['en', 'fa'] as $locale) {
        app()->setLocale($locale);
        adminMenuFreshManager();

        expect(adminMenuKeys(adminMenuPanel()->getNavigation())['tools'])
            ->not->toContain('url:/admin/keyless-report?tab=sales')
            ->toContain('acme.keyed-report');
    }
});

test('without overrides the core cms menu keeps its groups, items and order', function (): void {
    if (AdminSidebarNavigation::usesShopSidebar()) {
        $this->markTestSkipped('The shop sidebar layout is active in this database.');
    }

    $keys = adminMenuKeys(adminMenuNavigationWith(new MksineNavigationManager));
    $core = array_map(
        fn (array $items): array => array_values(array_filter(
            $items,
            fn (string $key): bool => str_starts_with($key, 'Miran\\Mksine\\') || str_starts_with($key, 'BezhanSalleh\\'),
        )),
        $keys,
    );

    expect(array_keys($core))->toBe(['', 'media', 'content', 'appearance', 'access_control', 'tools', 'system'])
        ->and($core[''])->toBe([MksineDashboard::class, ManagePlugins::class, SettingsCluster::class])
        ->and($core['media'])->toBe([
            MediaResource::class,
            MediaResource::class . '@create',
        ])
        ->and($core['content'])->toBe([
            PostResource::class,
            PageResource::class,
            CategoryResource::class,
            PageBuilderSectionResource::class,
            CommentResource::class,
            TagResource::class,
        ])
        ->and($core['appearance'])->toBe([
            ThemeManager::class,
            MenuResource::class,
            MenuLocationResource::class,
        ])
        ->and($core['access_control'])->toBe([
            UserResource::class,
            RoleResource::class,
        ])
        ->and($core['tools'])->toBe([
            Languages::class,
            GeoStateResource::class,
            ConsoleTerminal::class,
        ])
        ->and($core['system'])->toBe([SystemUpdate::class]);
});

test('plugins filter navigation groups through the mksine hook', function (): void {
    $received = null;

    Hooks::addFilter(MksineNavigationManager::GROUPS_FILTER, function (array $groups, Panel $panel) use (&$received): array {
        $received = $panel->getId();

        return array_map(function (NavigationGroup $group): NavigationGroup {
            return $group->items(collect($group->getItems())
                ->reject(fn (NavigationItem $item): bool => $item->getKey() === ConsoleTerminal::class)
                ->all());
        }, $groups);
    });

    $keys = adminMenuKeys(adminMenuNavigationWith(new MksineNavigationManager));

    expect($received)->toBe('admin')
        ->and($keys['tools'])->not->toContain(ConsoleTerminal::class)
        ->and($keys['tools'])->toContain(Languages::class);
});

test('stored overrides run after the plugin filter', function (): void {
    Hooks::addFilter(MksineNavigationManager::GROUPS_FILTER, function (array $groups): array {
        foreach ($groups as $group) {
            foreach (collect($group->getItems())->all() as $item) {
                $item->label('Filtered ' . $item->getLabel());
            }
        }

        return $groups;
    });

    adminMenuStore(['items' => [Languages::class => ['placement' => 'default', 'label' => 'Translations']]]);

    $labels = collect(adminMenuNavigationWith(new MksineNavigationManager))
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->mapWithKeys(fn (NavigationItem $item): array => [$item->getKey() => $item->getLabel()]);

    expect($labels[Languages::class])->toBe('Translations')
        ->and($labels[ConsoleTerminal::class])->toStartWith('Filtered ');
});

test('panels without the mksine plugin are left to filament', function (): void {
    adminMenuStore(['items' => [Languages::class => ['placement' => 'hidden']]]);

    $panel = Panel::make()->id('other')->path('other');
    $manager = new class extends MksineNavigationManager
    {
        public function usePanel(Panel $panel): void
        {
            $this->panel = $panel;
        }
    };
    $manager->usePanel($panel);

    expect($manager->managesPanel())->toBeFalse()
        ->and($manager->getDefaultNavigation())->toBe([])
        ->and($manager->getSettingsItems())->toBe([]);
});

test('the default navigation stays available to the editor after overrides apply', function (): void {
    adminMenuStore(['items' => [Languages::class => ['placement' => 'hidden']]]);

    $manager = adminMenuFreshManager();
    $sidebar = adminMenuKeys(adminMenuPanel()->getNavigation());
    $default = adminMenuKeys($manager->getDefaultNavigation());

    expect($sidebar['tools'])->not->toContain(Languages::class)
        ->and($default['tools'])->toContain(Languages::class);
});
