<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\ConsoleTerminal;
use Miran\Mksine\Filament\Pages\Languages;
use Miran\Mksine\Filament\Pages\MksineDashboard;
use Miran\Mksine\Filament\Pages\Settings\SettingsMenuPage;
use Miran\Mksine\Filament\Pages\SystemUpdate;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuOverrides;
use Miran\Mksine\Models\Setting;

require_once __DIR__ . '/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    adminMenuPanel();
});

/**
 * @return array<string, mixed>
 */
function adminMenuStored(): array
{
    $raw = Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->value('value');

    return is_string($raw) ? json_decode($raw, true) : [];
}

test('non super admins cannot open the menu editor', function (): void {
    $this->actingAs(User::factory()->create());

    expect(SettingsMenuPage::canAccess())->toBeFalse()
        ->and(SettingsMenuPage::shouldRegisterNavigation())->toBeFalse();

    $this->get(SettingsMenuPage::getUrl())->assertForbidden();
});

test('non super admins cannot save through the page methods', function (): void {
    $this->actingAs(User::factory()->create());

    expect(fn () => (new SettingsMenuPage)->saveItem(Languages::class, ['placement' => 'hidden']))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => (new SettingsMenuPage)->resetMenu())
        ->toThrow(AuthorizationException::class);
});

test('super admins see every sidebar item grouped by its default group', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    $this->get(SettingsMenuPage::getUrl())
        ->assertOk()
        ->assertSee(__('mksine::admin_menu.title'))
        ->assertSee(__('mksine::admin_menu.hidden_notice'))
        ->assertSee(__('mksine::admin_menu.preview.heading'));

    Livewire::test(SettingsMenuPage::class)
        ->assertCanSeeTableRecords([Languages::class, ConsoleTerminal::class, SettingsCluster::class, SystemUpdate::class])
        ->assertTableColumnStateSet('default_group_label', __('mksine::common.tools'), Languages::class)
        ->assertTableColumnStateSet('placement', 'default', Languages::class)
        ->assertTableColumnStateSet('sort', 10, Languages::class);
});

test('editing an item stores the override and reloads the page', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->callAction(TestAction::make('edit')->table(ConsoleTerminal::class), data: [
            'placement' => 'group',
            'group' => 'system',
            'sort' => 5,
            'label' => 'Terminal',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified(__('mksine::admin_menu.notifications.saved'))
        ->assertRedirect(SettingsMenuPage::getUrl());

    expect(adminMenuStored())->toBe([
        'items' => [ConsoleTerminal::class => ['placement' => 'group', 'group' => 'system', 'sort' => 5, 'label' => 'Terminal']],
        'groups' => [],
    ]);
});

test('moving to a group requires a target group', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->callAction(TestAction::make('edit')->table(Languages::class), data: [
            'placement' => 'group',
            'group' => null,
        ])
        ->assertHasFormErrors(['group' => 'required']);

    expect(adminMenuStored())->toBe([]);
});

test('protected items do not offer hidden or settings placements', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->mountAction(TestAction::make('edit')->table(SettingsCluster::class))
        ->assertFormFieldExists('placement', function ($field): bool {
            return array_keys($field->getOptions()) === ['default', 'group'];
        });

    (new SettingsMenuPage)->saveItem(SettingsCluster::class, ['placement' => 'hidden', 'label' => 'Config']);

    expect(adminMenuStored()['items'][SettingsCluster::class])->toBe(['placement' => 'default', 'label' => 'Config']);
});

test('regular items offer every placement in the edit form', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->mountAction(TestAction::make('edit')->table(MksineDashboard::class))
        ->assertFormFieldExists('placement', function ($field): bool {
            return array_keys($field->getOptions()) === ['default', 'hidden', 'group', 'settings'];
        });
});

test('the hide button hides an item in one click and show brings it back', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    adminMenuStore(['items' => [Languages::class => ['placement' => 'group', 'group' => 'system', 'sort' => 3, 'label' => 'Translations']]]);

    Livewire::test(SettingsMenuPage::class)
        ->assertActionVisible(TestAction::make('toggleHidden')->table(Languages::class))
        ->assertActionHidden(TestAction::make('toggleHidden')->table(SettingsCluster::class))
        ->callAction(TestAction::make('toggleHidden')->table(Languages::class))
        ->assertNotified(__('mksine::admin_menu.notifications.saved'));

    expect(adminMenuStored()['items'][Languages::class])->toBe(['placement' => 'hidden', 'sort' => 3, 'label' => 'Translations']);

    adminMenuFreshManager();
    expect(adminMenuKeys(adminMenuPanel()->getNavigation())['tools'] ?? [])->not->toContain(Languages::class);

    Livewire::test(SettingsMenuPage::class)
        ->callAction(TestAction::make('toggleHidden')->table(Languages::class));

    expect(adminMenuStored()['items'][Languages::class])->toBe(['placement' => 'default', 'sort' => 3, 'label' => 'Translations']);
});

test('editing groups stores sort and label overrides', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->callAction('editGroups', data: [
            'groups' => [
                'tools' => ['sort' => 5, 'label' => 'Utilities'],
                'system' => ['sort' => null, 'label' => null],
            ],
        ])
        ->assertHasNoFormErrors()
        ->assertRedirect(SettingsMenuPage::getUrl());

    expect(adminMenuStored()['groups'])->toBe(['tools' => ['sort' => 5, 'label' => 'Utilities']]);
});

test('groups can be hidden from the editor', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    Livewire::test(SettingsMenuPage::class)
        ->callAction('editGroups', data: [
            'groups' => [
                'tools' => ['hidden' => true],
                'system' => ['hidden' => false],
            ],
        ])
        ->assertHasNoFormErrors();

    expect(adminMenuStored()['groups'])->toBe(['tools' => ['hidden' => true]]);

    adminMenuFreshManager();

    expect(adminMenuKeys(adminMenuPanel()->getNavigation()))->not->toHaveKey('tools');

    Livewire::test(SettingsMenuPage::class)
        ->assertCanSeeTableRecords([Languages::class])
        ->assertTableColumnStateSet('placement', 'default', Languages::class);
});

test('the preview shows the resulting sidebar', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    adminMenuStore([
        'items' => [
            ConsoleTerminal::class => ['placement' => 'group', 'group' => 'system', 'label' => 'Terminal'],
            Languages::class => ['placement' => 'settings'],
        ],
        'groups' => ['system' => ['label' => 'Platform']],
    ]);

    $this->get(SettingsMenuPage::getUrl())
        ->assertOk()
        ->assertSeeInOrder(['Platform', 'Terminal'])
        ->assertSee(__('mksine::admin_menu.preview.settings_heading', ['group' => __('mksine::admin_menu.settings_navigation_group')]));
});

test('reset removes the setting', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    adminMenuStore(['items' => [Languages::class => ['placement' => 'hidden']]]);

    Livewire::test(SettingsMenuPage::class)
        ->callAction('reset')
        ->assertNotified(__('mksine::admin_menu.notifications.reset'))
        ->assertRedirect(SettingsMenuPage::getUrl());

    expect(Setting::query()->where('key', AdminMenuOverrides::SETTING_KEY)->exists())->toBeFalse();
});

test('admin menu translations have the same keys in en, fa and ku', function (): void {
    $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($lines as $key => $value) {
            $keys = is_array($value)
                ? [...$keys, ...$flatten($value, "{$prefix}{$key}.")]
                : [...$keys, "{$prefix}{$key}"];
        }

        return $keys;
    };

    $path = fn (string $locale): string => dirname(__DIR__, 3) . "/resources/lang/{$locale}/admin_menu.php";
    $en = $flatten(require $path('en'));

    foreach (['fa', 'ku'] as $locale) {
        expect($flatten(require $path($locale)))->toBe($en);
    }

    app()->setLocale('fa');

    expect(__('mksine::admin_menu.navigation_label'))->toBe('منوی مدیریت')
        ->and(__('mksine::admin_menu.settings_navigation_group'))->toBe('ابزارهای بیشتر');
});
