<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Core\Hooks\SettingsTabManager;
use Miran\Mksine\Filament\Pages\Settings\SettingsAppearancePage;
use Miran\Mksine\Filament\Pages\Settings\SettingsExtensionsPage;
use Miran\Mksine\Livewire\MyAppearance;
use Miran\Mksine\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * @param  list<string>  $permissions
 */
function appearanceUserWith(array $permissions): User
{
    $role = Role::findOrCreate('ztest-appearance-' . md5(implode(',', $permissions)), 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function appearanceSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web'));

    return $user;
}

function freshAppearanceRepository(): AppearanceRepository
{
    app()->forgetScopedInstances();

    return app(AppearanceRepository::class);
}

test('the login page shows the company look to guests', function (): void {
    freshAppearanceRepository()->saveCompany(Appearance::fromArray(['primary' => '#047857', 'rail' => 'dark']));
    app()->forgetScopedInstances();

    $this->get(Filament::getPanel('admin')->getLoginUrl())
        ->assertOk()
        ->assertSee('--primary-600:#047857;', false)
        ->assertSee('data-mks-appearance', false);
});

test('the login page stays untouched without a company look', function (): void {
    $this->get(Filament::getPanel('admin')->getLoginUrl())
        ->assertOk()
        ->assertDontSee('data-mks-appearance', false);
});

test('any signed-in user changes their own look but not the company look', function (): void {
    $user = appearanceUserWith(['View:Dashboard']);
    $this->actingAs($user);

    Livewire::test(MyAppearance::class)
        ->call('setPart', 'mode', 'dark')
        ->call('setPrimary', '#A21CAF')
        ->call('setPart', 'rail', 'nonsense')
        ->assertSet('look.primary', '#a21caf')
        ->assertSet('look.rail', null)
        ->call('saveAppearance');

    $repository = freshAppearanceRepository();

    expect($repository->personal($user->id)->toArray())->toBe(['primary' => '#a21caf', 'mode' => 'dark'])
        ->and($repository->company()->isEmpty())->toBeTrue()
        ->and(AppearanceRepository::canEditCompany($user))->toBeFalse();

    Livewire::test(SettingsAppearancePage::class)->assertForbidden();

    Livewire::test(MyAppearance::class)->call('useCompanyLook');

    expect(freshAppearanceRepository()->personal($user->id)->isEmpty())->toBeTrue();
});

test('a settings editor saves the company look, named looks and deletes them', function (): void {
    $editor = appearanceUserWith([AppearanceRepository::COMPANY_PERMISSION]);
    $this->actingAs($editor);

    Livewire::test(SettingsAppearancePage::class)
        ->assertOk()
        ->call('choosePreset', 'classic')
        ->assertSet('look.primary', '#1e40af')
        ->assertSet('look.rail', 'light')
        ->call('saveAppearance')
        ->set('saveName', 'Shop')
        ->call('saveNamedAppearance')
        ->assertHasNoErrors();

    $repository = freshAppearanceRepository();

    expect($repository->company()->toArray())->toMatchArray(['preset' => 'classic', 'primary' => '#1e40af', 'rail' => 'light'])
        ->and(array_column($repository->saved(), 'name'))->toBe(['Shop']);

    Livewire::test(SettingsAppearancePage::class)
        ->set('saveName', str_repeat('x', AppearanceRepository::MAX_NAME_LENGTH + 1))
        ->call('saveNamedAppearance')
        ->assertHasErrors('saveName')
        ->call('deleteNamedAppearance', 'Shop');

    expect(freshAppearanceRepository()->saved())->toBe([]);
});

test('super admins edit the company look without the permission being granted', function (): void {
    $this->actingAs(appearanceSuperAdmin());

    Livewire::test(SettingsAppearancePage::class)
        ->call('setPart', 'radius', 'round')
        ->call('saveAppearance');

    expect(freshAppearanceRepository()->company()->toArray())->toBe(['radius' => 'round']);
});

test('company writes check the permission on every call, not only on page load', function (): void {
    $editor = appearanceUserWith([AppearanceRepository::COMPANY_PERMISSION]);
    $this->actingAs($editor);

    $page = Livewire::test(SettingsAppearancePage::class)->call('setPart', 'rail', 'dark');
    $namePage = Livewire::test(SettingsAppearancePage::class)->set('saveName', 'Sneaky');

    $editor->roles()->detach();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $editor->unsetRelation('roles')->unsetRelation('permissions');

    $page->call('saveAppearance')->assertForbidden();
    $namePage->call('saveNamedAppearance')->assertForbidden();

    expect(Setting::query()->where('key', 'like', AppearanceRepository::COMPANY_KEY . '%')->exists())->toBeFalse();
});

test('the generic settings form cannot write any appearance key', function (): void {
    $alice = User::factory()->create();
    $userKey = AppearanceRepository::userKey($alice->id);

    app(SettingsTabManager::class)->registerTab(
        id: 'ztest-appearance-leak',
        label: 'Leak',
        schema: [
            TextInput::make(AppearanceRepository::COMPANY_KEY),
            TextInput::make(AppearanceRepository::SAVED_KEY),
            TextInput::make($userKey),
            TextInput::make('ztest_plain_setting'),
        ],
    );

    $this->actingAs(appearanceSuperAdmin());

    Livewire::test(SettingsExtensionsPage::class)
        ->set('data.' . AppearanceRepository::COMPANY_KEY, '{"primary":"#000000"}')
        ->set('data.' . AppearanceRepository::SAVED_KEY, '[{"name":"x","look":{}}]')
        ->set('data.' . $userKey, '{"mode":"dark"}')
        ->set('data.ztest_plain_setting', 'kept')
        ->call('saveData')
        ->assertHasNoErrors();

    expect(Setting::query()->where('key', 'like', AppearanceRepository::COMPANY_KEY . '%')->exists())->toBeFalse()
        ->and(Setting::query()->where('key', 'ztest_plain_setting')->value('value'))->toBe('kept');
});
