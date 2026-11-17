<?php

declare(strict_types=1);

use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;
use Miran\Mksine\Filament\Resources\Comments\CommentResource;
use Miran\Mksine\Filament\Resources\Media\MediaResource;
use Miran\Mksine\Filament\Resources\MenuLocations\MenuLocationResource;
use Miran\Mksine\Filament\Resources\Menus\MenuResource;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Filament\Resources\Posts\PostResource;
use Miran\Mksine\Filament\Resources\Tags\TagResource;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Policies\PolicyRegistrar;
use Miran\Mksine\Policies\PostPolicy;
use Miran\Mksine\Policies\ShieldModelPolicy;
use Miran\Mksine\Policies\UserPolicy;
use Miran\Mksine\Tests\Fixtures\Models\PanelMember as PanelMemberFixture;
use Miran\Mksine\Tests\Fixtures\Policies\PostPolicy as AppPostPolicyFixture;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__ . '/../../Fixtures/Policies/PostPolicy.php';
require_once __DIR__ . '/../../Fixtures/Models/PanelMember.php';

uses(RefreshDatabase::class);

/** A namespace with no policies: what a host without `app/Policies` looks like. */
const MISSING_APP_POLICY_NAMESPACE = 'Miran\\Mksine\\Tests\\Missing\\Policies\\';

/**
 * @param  list<string>  $permissions
 */
function policyUserWith(array $permissions): User
{
    $role = Role::findOrCreate('ztest-policy-' . md5(implode(',', $permissions)), 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    PolicyRegistrar::register(MISSING_APP_POLICY_NAMESPACE);
});

test('every core model gets the package policy when the app has none', function (): void {
    foreach (PolicyRegistrar::defaults() as $model => $defaultPolicy) {
        expect(Gate::getPolicyFor($model))->toBeInstanceOf($defaultPolicy)
            ->and(Gate::getPolicyFor($model))->toBeInstanceOf(ShieldModelPolicy::class);
    }
});

test('an order only user is locked out of the core resources and roles', function (): void {
    $this->actingAs(policyUserWith(['ViewAny:Order', 'View:Order', 'Create:Order', 'Update:Order']));

    foreach ([
        PostResource::class,
        PageResource::class,
        CategoryResource::class,
        TagResource::class,
        CommentResource::class,
        MediaResource::class,
        MenuResource::class,
        MenuLocationResource::class,
        RoleResource::class,
    ] as $resource) {
        expect($resource::canAccess())->toBeFalse("{$resource} should be closed")
            ->and($resource::canCreate())->toBeFalse("{$resource} create should be closed");
    }

    $superAdmin = Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web');

    expect(RoleResource::canEdit($superAdmin))->toBeFalse()
        ->and(Gate::allows('update', $superAdmin))->toBeFalse();
});

test('abilities follow the matching shield permissions', function (): void {
    $this->actingAs(policyUserWith(['ViewAny:Post', 'View:Post', 'Update:Post']));

    $post = new Post;

    expect(PostResource::canAccess())->toBeTrue()
        ->and(Gate::allows('view', $post))->toBeTrue()
        ->and(Gate::allows('update', $post))->toBeTrue()
        ->and(Gate::allows('create', Post::class))->toBeFalse()
        ->and(Gate::allows('delete', $post))->toBeFalse()
        ->and(Gate::allows('deleteAny', Post::class))->toBeFalse()
        ->and(PageResource::canAccess())->toBeFalse();
});

test('bulk delete accepts the delete permission because shield does not generate delete any', function (): void {
    $this->actingAs(policyUserWith(['Delete:Post']));

    expect(Gate::allows('deleteAny', Post::class))->toBeTrue();
});

test('role permissions open the role resource', function (): void {
    $this->actingAs(policyUserWith(['ViewAny:Role', 'Update:Role']));

    expect(RoleResource::canAccess())->toBeTrue()
        ->and(RoleResource::canEdit(Role::findOrCreate('ztest-editable', 'web')))->toBeTrue()
        ->and(RoleResource::canCreate())->toBeFalse();
});

test('an app policy takes precedence over the package default', function (): void {
    PolicyRegistrar::register('Miran\\Mksine\\Tests\\Fixtures\\Policies\\');

    $this->actingAs(policyUserWith([]));

    expect(Gate::getPolicyFor(Post::class))->toBeInstanceOf(AppPostPolicyFixture::class)
        ->and(Gate::allows('viewAny', Post::class))->toBeTrue()
        ->and(PolicyRegistrar::resolve(PostPolicy::class, 'Miran\\Mksine\\Tests\\Fixtures\\Policies\\'))->toBe(AppPostPolicyFixture::class)
        ->and(PolicyRegistrar::resolve(PostPolicy::class, MISSING_APP_POLICY_NAMESPACE))->toBe(PostPolicy::class);
});

test('the service provider prefers app policies and falls back to the package', function (): void {
    $this->refreshApplication();

    $expected = class_exists('App\\Policies\\PostPolicy') ? 'App\\Policies\\PostPolicy' : PostPolicy::class;

    expect(Gate::getPolicyFor(Post::class))->toBeInstanceOf($expected);
});

test('permission names follow the shield permission config', function (): void {
    expect(ShieldModelPolicy::permissionKey('viewAny', 'Post'))->toBe('ViewAny:Post')
        ->and(ShieldModelPolicy::permissionKey('forceDeleteAny', 'MenuLocation'))->toBe('ForceDeleteAny:MenuLocation');

    config(['filament-shield.permissions.separator' => '_', 'filament-shield.permissions.case' => 'lower_snake']);

    expect(ShieldModelPolicy::permissionKey('viewAny', 'MenuLocation'))->toBe('view_any_menu_location');
});

test('geo countries are read only and imported cities cannot be deleted', function (): void {
    $this->actingAs(policyUserWith(['ViewAny:GeoCountry', 'View:GeoCountry', 'Update:GeoState', 'Delete:GeoCity']));

    $seedCity = new GeoCity(['source' => GeoSource::Seed]);
    $manualCity = new GeoCity(['source' => GeoSource::Manual]);

    expect(Gate::allows('viewAny', GeoCountry::class))->toBeTrue()
        ->and(Gate::allows('update', new GeoCountry))->toBeFalse()
        ->and(Gate::allows('viewAny', GeoCity::class))->toBeFalse()
        ->and(Gate::allows('update', $manualCity))->toBeTrue()
        ->and(Gate::allows('delete', $manualCity))->toBeTrue()
        ->and(Gate::allows('delete', $seedCity))->toBeFalse();
});

test('a user model without any policy gets the package user policy', function (): void {
    config()->set('mksine.user_model', PanelMemberFixture::class);

    PolicyRegistrar::register(MISSING_APP_POLICY_NAMESPACE);

    $this->actingAs(policyUserWith(['ViewAny:Order']));

    expect(Gate::getPolicyFor(PanelMemberFixture::class))->toBeInstanceOf(UserPolicy::class)
        ->and(Gate::allows('viewAny', PanelMemberFixture::class))->toBeFalse()
        ->and(Gate::allows('update', new PanelMemberFixture))->toBeFalse();

    $this->actingAs(policyUserWith(['ViewAny:PanelMember', 'Update:PanelMember']));

    expect(Gate::allows('viewAny', PanelMemberFixture::class))->toBeTrue()
        ->and(Gate::allows('update', new PanelMemberFixture))->toBeTrue()
        ->and(Gate::allows('delete', new PanelMemberFixture))->toBeFalse();
});

test('an existing policy for the user model is kept', function (): void {
    config()->set('mksine.user_model', PanelMemberFixture::class);
    Gate::policy(PanelMemberFixture::class, AppPostPolicyFixture::class);

    PolicyRegistrar::register(MISSING_APP_POLICY_NAMESPACE);

    expect(Gate::getPolicyFor(PanelMemberFixture::class))->toBeInstanceOf(AppPostPolicyFixture::class);
});
