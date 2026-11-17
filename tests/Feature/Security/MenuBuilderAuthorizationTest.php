<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Filament\Pages\MenuBuilder;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $permissions
 */
function menuBuilderUserWith(array $permissions): User
{
    $role = Role::findOrCreate('ztest-menu-' . md5(implode(',', $permissions)), 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function menuBuilderMenuWithItem(): Menu
{
    $menu = Menu::query()->create(['name' => 'Main', 'slug' => 'main-' . uniqid()]);

    MenuItem::query()->create([
        'menu_id' => $menu->id,
        'type' => MenuItem::TYPE_CUSTOM_LINK,
        'label' => 'Home',
        'url' => 'https://example.test',
        'order' => 0,
        'target' => '_self',
    ]);

    return $menu;
}

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('panel users without update menu cannot open the menu builder', function (): void {
    $this->actingAs(menuBuilderUserWith(['ViewAny:Order']));

    expect(MenuBuilder::canAccess())->toBeFalse();

    $this->get(MenuBuilder::getUrl())->assertForbidden();
});

test('view only menu users cannot open the menu builder', function (): void {
    $this->actingAs(menuBuilderUserWith(['ViewAny:Menu', 'View:Menu']));

    expect(MenuBuilder::canAccess())->toBeFalse();
});

test('users with update menu can open the menu builder and save', function (): void {
    $menu = menuBuilderMenuWithItem();
    $this->actingAs(menuBuilderUserWith(['ViewAny:Menu', 'Update:Menu']));

    expect(MenuBuilder::canAccess())->toBeTrue();

    $this->get(MenuBuilder::getUrl(['menu' => $menu->id]))->assertOk();

    Livewire::test(MenuBuilder::class, ['menu' => $menu->id])
        ->assertActionVisible('save')
        ->callAction('save')
        ->assertNotified(__('mksine::menu_builder.menu_saved'));
});

test('saving and editing through page methods requires update menu', function (string $method, array $arguments): void {
    $menu = menuBuilderMenuWithItem();
    $itemId = (int) $menu->items()->value('id');
    $this->actingAs(menuBuilderUserWith(['ViewAny:Menu']));

    $page = new MenuBuilder;
    $page->selectedMenu = $menu;
    $page->menuItems = [['id' => $itemId, 'children' => []]];

    $arguments = array_map(fn (mixed $argument): mixed => $argument === 'ITEM' ? $itemId : $argument, $arguments);

    expect(fn () => $page->{$method}(...$arguments))->toThrow(AuthorizationException::class);

    expect(MenuItem::query()->whereKey($itemId)->exists())->toBeTrue();
})->with([
    'saveMenu' => ['saveMenu', []],
    'removeItem' => ['removeItem', ['ITEM']],
    'indentItem' => ['indentItem', ['ITEM']],
    'outdentItem' => ['outdentItem', ['ITEM']],
    'updateMenuStructure' => ['updateMenuStructure', [[['id' => 'ITEM', 'children' => []]]]],
    'addItemFromSource' => ['addItemFromSource', ['custom_link']],
]);

test('livewire requests are rejected once the user loses update menu', function (): void {
    $menu = menuBuilderMenuWithItem();
    $user = menuBuilderUserWith(['ViewAny:Menu', 'Update:Menu']);
    $this->actingAs($user);

    $component = Livewire::test(MenuBuilder::class, ['menu' => $menu->id]);

    $user->roles()->first()->revokePermissionTo('Update:Menu');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->unsetRelation('roles')->unsetRelation('permissions');

    $component->call('saveMenu')->assertForbidden();
});
