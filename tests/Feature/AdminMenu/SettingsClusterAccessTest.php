<?php

declare(strict_types=1);

use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\Settings\SettingsGeneralPage;
use Miran\Mksine\Filament\Pages\Settings\SettingsGeoPage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__ . '/AdminMenuTestHelpers.php';

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    adminMenuPanel();
});

/**
 * @param  list<string>  $permissions
 */
function settingsClusterUserWith(array $permissions): User
{
    $role = Role::findOrCreate('ztest-settings-' . md5(implode(',', $permissions)), 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * @return list<string>
 */
function settingsClusterSidebarKeys(): array
{
    adminMenuFreshManager();

    return array_merge(...array_values(adminMenuKeys(adminMenuPanel()->getNavigation())));
}

test('the settings item is hidden and closed when no settings page is allowed', function (): void {
    $this->actingAs(settingsClusterUserWith(['ViewAny:Order', 'Update:Order']));

    expect(SettingsCluster::canAccess())->toBeFalse()
        ->and(SettingsCluster::shouldRegisterNavigation())->toBeFalse()
        ->and(settingsClusterSidebarKeys())->not->toContain(SettingsCluster::class);

    $this->get(SettingsCluster::getUrl())->assertForbidden();
});

test('the settings item appears when one settings page is allowed', function (): void {
    $permission = array_key_first(FilamentShield::getPages()[SettingsGeneralPage::class]['permissions'] ?? []);

    expect($permission)->toBeString();

    $this->actingAs(settingsClusterUserWith([$permission]));

    expect(SettingsGeneralPage::canAccess())->toBeTrue()
        ->and(SettingsCluster::canAccess())->toBeTrue()
        ->and(SettingsCluster::shouldRegisterNavigation())->toBeTrue()
        ->and(settingsClusterSidebarKeys())->toContain(SettingsCluster::class);

    $this->get(SettingsCluster::getUrl())->assertRedirect(SettingsGeneralPage::getUrl());
});

test('each settings page checks its own shield permission', function (): void {
    $pages = FilamentShield::getPages();
    $generalPermission = array_key_first($pages[SettingsGeneralPage::class]['permissions']);
    $geoPermission = array_key_first($pages[SettingsGeoPage::class]['permissions']);

    expect($generalPermission)->not->toBe($geoPermission);

    $this->actingAs(settingsClusterUserWith([$generalPermission]));

    // The geo page is checked first on purpose: it must not decide the general page's permission.
    expect(SettingsGeoPage::canAccess())->toBeFalse()
        ->and(SettingsGeneralPage::canAccess())->toBeTrue()
        ->and(SettingsGeoPage::canAccess())->toBeFalse();
});

test('super admins keep the settings item even with no explicit permissions', function (): void {
    $role = (string) config('filament-shield.super_admin.name', 'super_admin');
    Role::findOrCreate($role, 'web');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole($role);

    $this->actingAs($superAdmin);

    expect(SettingsCluster::canAccess())->toBeTrue()
        ->and(settingsClusterSidebarKeys())->toContain(SettingsCluster::class);
});

test('super admins cannot hide the settings item through menu overrides', function (): void {
    $this->actingAs(adminMenuSuperAdmin());

    adminMenuStore(['items' => [SettingsCluster::class => ['placement' => 'hidden']]]);

    expect(settingsClusterSidebarKeys())->toContain(SettingsCluster::class);
});
