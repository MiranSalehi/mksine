<?php

declare(strict_types=1);

use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\Settings\SettingsMenuPage;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuOverrides;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuPlacement;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Models\Setting;

require_once __DIR__ . '/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
});

/**
 * @return array<string, list<string>>
 */
function adminMenuApply(array $overrides): array
{
    adminMenuStore($overrides);

    return adminMenuKeys(app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups())->groups);
}

test('no setting returns the groups untouched', function (): void {
    $groups = adminMenuSampleGroups();
    $resolution = app(AdminMenuOverrides::class)->apply($groups);

    expect($resolution->groups)->toBe($groups)
        ->and($resolution->settingsItems)->toBe([])
        ->and(app(AdminMenuOverrides::class)->isEmpty())->toBeTrue();
});

test('hidden items leave the sidebar', function (): void {
    expect(adminMenuApply(['items' => ['App\\Languages' => ['placement' => 'hidden']]]))->toBe([
        '' => ['App\\Dashboard', SettingsCluster::class],
        'content' => ['App\\Posts', 'App\\Pages'],
        'tools' => ['App\\Console'],
        'system' => ['App\\Update'],
    ]);
});

test('items move to another group at their sort', function (): void {
    $keys = adminMenuApply(['items' => [
        'App\\Console' => ['placement' => 'group', 'group' => 'content', 'sort' => 2],
    ]]);

    expect($keys['content'])->toBe(['App\\Posts', 'App\\Pages', 'App\\Console'])
        ->and($keys['tools'])->toBe(['App\\Languages']);

    $keys = adminMenuApply(['items' => [
        'App\\Console' => ['placement' => 'group', 'group' => 'content', 'sort' => 0],
    ]]);

    expect($keys['content'])->toBe(['App\\Console', 'App\\Posts', 'App\\Pages']);
});

test('moved items carry their new group identity', function (): void {
    adminMenuStore(['items' => ['App\\Console' => ['placement' => 'group', 'group' => 'content']]]);

    $content = app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups())->groups[1];
    $console = collect($content->getItems())->first(fn (NavigationItem $item): bool => $item->getKey() === 'App\\Console');

    expect($console->getGroup())->toBe(AdminNavigationGroup::Content)
        ->and(AdminMenuOverrides::groupKey($content))->toBe('content');
});

test('moving into a group without items creates it in enum order', function (): void {
    $keys = adminMenuApply(['items' => ['App\\Languages' => ['placement' => 'group', 'group' => 'orders']]]);

    expect(array_keys($keys))->toBe(['', 'content', 'orders', 'tools', 'system'])
        ->and($keys['orders'])->toBe(['App\\Languages']);
});

test('a group left without items disappears', function (): void {
    $keys = adminMenuApply(['items' => ['App\\Update' => ['placement' => 'group', 'group' => 'tools']]]);

    expect(array_keys($keys))->toBe(['', 'content', 'tools'])
        ->and($keys['tools'])->toBe(['App\\Languages', 'App\\Console', 'App\\Update']);
});

test('settings placement removes the item and returns it for the settings sub-navigation', function (): void {
    adminMenuStore(['items' => ['App\\Languages' => ['placement' => 'settings', 'label' => 'Translations']]]);

    $resolution = app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups());

    expect(adminMenuKeys($resolution->groups)['tools'])->toBe(['App\\Console'])
        ->and($resolution->settingsItems)->toHaveCount(1)
        ->and($resolution->settingsItems[0]->getKey())->toBe('App\\Languages')
        ->and($resolution->settingsItems[0]->getLabel())->toBe('Translations')
        ->and($resolution->settingsItems[0]->getUrl())->toBe('/admin/languages')
        ->and($resolution->settingsItems[0]->getGroup())->toBe(__('mksine::admin_menu.settings_navigation_group'));
});

test('labels and sorts change in place', function (): void {
    adminMenuStore(['items' => [
        'App\\Pages' => ['placement' => 'default', 'sort' => -5, 'label' => 'Static pages'],
    ]]);

    $content = app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups())->groups[1];
    $items = collect($content->getItems())->values();

    expect($items->map->getKey()->all())->toBe(['App\\Pages', 'App\\Posts'])
        ->and($items[0]->getLabel())->toBe('Static pages')
        ->and($items[0]->getSort())->toBe(-5);
});

test('unknown item keys and invalid entries are ignored', function (): void {
    $expected = adminMenuKeys(adminMenuSampleGroups());

    expect(adminMenuApply(['items' => [
        'App\\Deleted\\Page' => ['placement' => 'hidden'],
        'App\\Posts' => ['placement' => 'group', 'group' => 'not-a-group'],
        'App\\Pages' => ['placement' => 'teleport'],
    ], 'groups' => ['not-a-group' => ['sort' => 1]]]))->toBe($expected)
        ->and(app(AdminMenuOverrides::class)->itemOverrides())->toHaveKey('App\\Deleted\\Page')
        ->and(app(AdminMenuOverrides::class)->itemOverrides())->not->toHaveKey('App\\Posts')
        ->and(app(AdminMenuOverrides::class)->groupOverrides())->toBe([]);
});

test('broken json is treated as no overrides', function (): void {
    adminMenuStore('{"items": [');

    expect(app(AdminMenuOverrides::class)->isEmpty())->toBeTrue()
        ->and(adminMenuKeys(app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups())->groups))
        ->toBe(adminMenuKeys(adminMenuSampleGroups()));
});

test('settings and the menu editor can never leave the sidebar', function (string $placement): void {
    adminMenuStore(['items' => [
        SettingsCluster::class => ['placement' => $placement],
        SettingsMenuPage::class => ['placement' => $placement],
    ]]);

    $resolution = app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups());

    expect(adminMenuKeys($resolution->groups)[''])->toContain(SettingsCluster::class)
        ->and($resolution->settingsItems)->toBe([])
        ->and(AdminMenuOverrides::isProtected(SettingsMenuPage::class))->toBeTrue();
})->with(['hidden', 'settings']);

test('protected items can still move to another group', function (): void {
    $keys = adminMenuApply(['items' => [SettingsCluster::class => ['placement' => 'group', 'group' => 'system']]]);

    expect($keys[''])->toBe(['App\\Dashboard'])
        ->and($keys['system'])->toBe([SettingsCluster::class, 'App\\Update']);
});

test('group sort overrides reorder groups and top level stays first', function (): void {
    $keys = adminMenuApply(['groups' => ['system' => ['sort' => 5]]]);

    expect(array_keys($keys))->toBe(['', 'system', 'content', 'tools']);

    $keys = adminMenuApply(['groups' => ['content' => ['sort' => 95]]]);

    expect(array_keys($keys))->toBe(['', 'tools', 'content', 'system']);
});

test('group labels can be renamed', function (): void {
    adminMenuStore(['groups' => ['tools' => ['label' => 'Utilities']]]);

    $groups = app(AdminMenuOverrides::class)->apply(adminMenuSampleGroups())->groups;

    expect($groups[2]->getLabel())->toBe('Utilities')
        ->and(adminMenuSampleGroups()[2]->getLabel())->toBe('Tools');
});

test('apply never mutates the groups it receives', function (): void {
    $groups = adminMenuSampleGroups();
    $before = adminMenuSnapshot($groups);

    adminMenuStore([
        'items' => [
            'App\\Posts' => ['placement' => 'group', 'group' => 'tools', 'sort' => 1, 'label' => 'Articles'],
            'App\\Update' => ['placement' => 'hidden'],
        ],
        'groups' => ['content' => ['label' => 'Writing', 'sort' => 1]],
    ]);

    app(AdminMenuOverrides::class)->apply($groups);

    expect(adminMenuSnapshot($groups))->toBe($before);
});

test('a hidden group leaves the sidebar with its items', function (): void {
    expect(adminMenuApply(['groups' => ['tools' => ['hidden' => true]]]))->toBe([
        '' => ['App\\Dashboard', SettingsCluster::class],
        'content' => ['App\\Posts', 'App\\Pages'],
        'system' => ['App\\Update'],
    ]);
});

test('items moved out of a hidden group stay visible and items moved in are hidden', function (): void {
    $keys = adminMenuApply([
        'items' => [
            'App\\Console' => ['placement' => 'group', 'group' => 'system'],
            'App\\Posts' => ['placement' => 'group', 'group' => 'tools'],
        ],
        'groups' => ['tools' => ['hidden' => true]],
    ]);

    expect(array_keys($keys))->toBe(['', 'content', 'system'])
        ->and($keys['content'])->toBe(['App\\Pages'])
        ->and($keys['system'])->toBe(['App\\Console', 'App\\Update']);
});

test('protected items stay visible inside a hidden group', function (): void {
    $keys = adminMenuApply([
        'items' => [SettingsCluster::class => ['placement' => 'group', 'group' => 'tools']],
        'groups' => ['tools' => ['hidden' => true]],
    ]);

    expect($keys['tools'])->toBe([SettingsCluster::class]);
});

test('only a hidden flag of true is stored for groups', function (): void {
    app(AdminMenuOverrides::class)->save(['groups' => [
        'tools' => ['hidden' => true],
        'system' => ['hidden' => false],
        'media' => ['hidden' => '1'],
    ]]);

    $stored = json_decode((string) Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->value('value'), true);

    expect($stored['groups'])->toBe(['tools' => ['hidden' => true], 'media' => ['hidden' => true]])
        ->and(app(AdminMenuOverrides::class)->groupOverride('tools'))->toBe(['sort' => null, 'label' => null, 'hidden' => true]);
});

test('default group sorts follow the enum order', function (): void {
    expect(AdminMenuOverrides::defaultGroupSort(AdminNavigationGroup::Media))->toBe(10)
        ->and(AdminMenuOverrides::defaultGroupSort(AdminNavigationGroup::Tools))->toBe(90)
        ->and(AdminMenuOverrides::defaultGroupSort(AdminNavigationGroup::System))->toBe(100);
});

test('the setting is read once per request and re-read after a save', function (): void {
    adminMenuStore(['items' => ['App\\Languages' => ['placement' => 'hidden']]]);

    $overrides = app(AdminMenuOverrides::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $overrides->apply(adminMenuSampleGroups());
    $overrides->apply(adminMenuSampleGroups());
    $overrides->itemOverrides();

    $settingReads = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'settings'))->count();

    $overrides->save(['items' => ['App\\Console' => ['placement' => 'hidden']]]);
    $keys = adminMenuKeys($overrides->apply(adminMenuSampleGroups())->groups);

    DB::disableQueryLog();

    expect($settingReads)->toBe(1)
        ->and($keys['tools'])->toBe(['App\\Languages'])
        ->and(app(AdminMenuOverrides::class))->toBe($overrides);
});

test('save stores only non-default entries in the documented shape', function (): void {
    app(AdminMenuOverrides::class)->save([
        'items' => [
            'App\\Posts' => ['placement' => 'group', 'group' => 'products', 'sort' => '25', 'label' => ' Articles '],
            'App\\Pages' => ['placement' => 'default', 'group' => 'tools', 'sort' => null, 'label' => ''],
            'App\\Languages' => ['placement' => 'settings', 'group' => 'tools'],
            'App\\Update' => ['placement' => 'hidden'],
            'App\\Console' => ['placement' => 'default', 'sort' => 7.0],
        ],
        'groups' => [
            'tools' => ['sort' => 90, 'label' => null],
            'content' => ['sort' => '', 'label' => ''],
        ],
    ]);

    $stored = json_decode((string) Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->value('value'), true);

    expect($stored)->toBe([
        'items' => [
            'App\\Posts' => ['placement' => 'group', 'group' => 'products', 'sort' => 25, 'label' => 'Articles'],
            'App\\Languages' => ['placement' => 'settings'],
            'App\\Update' => ['placement' => 'hidden'],
            'App\\Console' => ['placement' => 'default', 'sort' => 7],
        ],
        'groups' => [
            'tools' => ['sort' => 90],
        ],
    ])->and(app(AdminMenuOverrides::class)->itemOverride('App\\Update')['placement'])->toBe(AdminMenuPlacement::Hidden);
});

test('saving only defaults or resetting deletes the setting', function (): void {
    adminMenuStore(['items' => ['App\\Update' => ['placement' => 'hidden']]]);

    app(AdminMenuOverrides::class)->save(['items' => ['App\\Update' => ['placement' => 'default']]]);

    expect(Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->exists())->toBeFalse();

    adminMenuStore(['items' => ['App\\Update' => ['placement' => 'hidden']]]);
    app(AdminMenuOverrides::class)->reset();

    expect(Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->exists())->toBeFalse()
        ->and(app(AdminMenuOverrides::class)->isEmpty())->toBeTrue();
});

test('overrides that do not fit the settings column are rejected', function (): void {
    $items = [];

    for ($i = 0; $i < 200; $i++) {
        $items["App\\Filament\\Resources\\VeryLongResourceName{$i}Resource"] = ['placement' => 'hidden'];
    }

    expect(fn () => app(AdminMenuOverrides::class)->save(['items' => $items]))
        ->toThrow(LengthException::class)
        ->and(Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->exists())->toBeFalse();
});
