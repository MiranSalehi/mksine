<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Miran\Mksine\Filament\Resources\Posts\PostResource;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Policies\PolicyRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    // Package defaults, so the result does not depend on the host's app/Policies.
    PolicyRegistrar::register('Miran\\Mksine\\Tests\\Missing\\Policies\\');

    // A permission created after the role was set up (e.g. by activating a plugin).
    Permission::findOrCreate('ViewAny:Post', 'web');
    Permission::findOrCreate('View:BrandNewSettingsPage', 'web');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function superAdminWithoutPermissions(): User
{
    $role = Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web');
    $role->syncPermissions([]);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('super admins pass permissions their role was never given', function (): void {
    $superAdmin = superAdminWithoutPermissions();

    expect($superAdmin->can('View:BrandNewSettingsPage'))->toBeTrue()
        ->and($superAdmin->can('ViewAny:Post'))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('viewAny', Post::class))->toBeTrue();

    $this->actingAs($superAdmin);

    expect(PostResource::canAccess())->toBeTrue();
});

test('an explicit policy refusal still applies to super admins', function (): void {
    $superAdmin = superAdminWithoutPermissions();

    expect(Gate::forUser($superAdmin)->allows('update', new GeoCountry))->toBeFalse()
        ->and(Gate::forUser($superAdmin)->allows('create', GeoCountry::class))->toBeFalse();
});

test('other users still need the permission', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole(Role::findOrCreate('ztest-editor', 'web'));

    expect($editor->can('View:BrandNewSettingsPage'))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('viewAny', Post::class))->toBeFalse();
});
