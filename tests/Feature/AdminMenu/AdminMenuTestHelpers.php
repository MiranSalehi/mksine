<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuOverrides;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;
use Miran\Mksine\Models\Setting;
use Spatie\Permission\Models\Role;

/**
 * Super admin that passes every Shield permission check (like production's Gate intercept),
 * so the whole menu is visible.
 */
function adminMenuSuperAdmin(): User
{
    $role = (string) config('filament-shield.super_admin.name', 'super_admin');
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);

    Gate::before(fn ($user): ?bool => method_exists($user, 'hasRole') && $user->hasRole($role) ? true : null);

    return $user;
}

function adminMenuPanel(): Panel
{
    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    return $panel;
}

/**
 * Admin panel navigation resolved by a specific manager (Filament's own or MKSine's).
 *
 * @return array<NavigationGroup>
 */
function adminMenuNavigationWith(NavigationManager $manager): array
{
    app()->instance(NavigationManager::class, $manager);

    try {
        return adminMenuPanel()->getNavigation();
    } finally {
        app()->forgetInstance(NavigationManager::class);
    }
}

/**
 * Fresh request-scoped manager and overrides (as on the next request).
 */
function adminMenuFreshManager(): NavigationManager
{
    adminMenuPanel();
    app()->forgetScopedInstances();

    return app(NavigationManager::class);
}

/**
 * @param  array<NavigationGroup>  $groups
 * @return list<array{group: string, label: ?string, items: list<array{key?: string, url?: ?string, label: string, sort: int}>}>
 */
function adminMenuSnapshot(array $groups, bool $withKeys = true): array
{
    return array_values(array_map(fn (NavigationGroup $group): array => [
        'group' => AdminMenuOverrides::groupKey($group),
        'label' => $group->getLabel(),
        'items' => array_values(array_map(fn (NavigationItem $item): array => [
            ...($withKeys ? ['key' => $item->getKey()] : ['url' => $item->getUrl()]),
            'label' => $item->getLabel(),
            'sort' => $item->getSort(),
        ], collect($group->getItems())->all())),
    ], $groups));
}

/**
 * @param  array<NavigationGroup>  $groups
 * @return array<string, list<string>>
 */
function adminMenuKeys(array $groups): array
{
    $keys = [];

    foreach ($groups as $group) {
        $keys[AdminMenuOverrides::groupKey($group)] = array_values(array_map(
            fn (NavigationItem $item): string => $item->getKey(),
            collect($group->getItems())->all(),
        ));
    }

    return $keys;
}

/**
 * Store the raw setting (as a previous save would have) and forget the memoized copy.
 *
 * @param  array<string, mixed>|string  $value
 */
function adminMenuStore(array | string $value): void
{
    Setting::query()->updateOrCreate(
        ['key' => AdminMenuOverrides::SETTING_KEY],
        ['value' => is_string($value) ? $value : json_encode($value)],
    );

    app()->forgetScopedInstances();
}

function adminMenuItem(string $key, AdminNavigationGroup | string | null $group, int $sort): NavigationItem
{
    return NavigationItem::make($key)
        ->key($key)
        ->group($group)
        ->sort($sort)
        ->url('/admin/' . strtolower(class_basename($key)));
}

function adminMenuGroup(?AdminNavigationGroup $group, NavigationItem ...$items): NavigationGroup
{
    $navigationGroup = $group === null
        ? NavigationGroup::make()
        : AdminSidebarNavigation::makeGroup($group);

    return $navigationGroup->items($items);
}

/**
 * Synthetic sidebar: top level (Dashboard, Settings), Content (Posts, Pages), Tools (Languages, Console), System (Update).
 *
 * @return list<NavigationGroup>
 */
function adminMenuSampleGroups(): array
{
    return [
        adminMenuGroup(
            null,
            adminMenuItem('App\\Dashboard', null, -2),
            adminMenuItem(SettingsCluster::class, null, 80),
        ),
        adminMenuGroup(
            AdminNavigationGroup::Content,
            adminMenuItem('App\\Posts', AdminNavigationGroup::Content, 1),
            adminMenuItem('App\\Pages', AdminNavigationGroup::Content, 2),
        ),
        adminMenuGroup(
            AdminNavigationGroup::Tools,
            adminMenuItem('App\\Languages', AdminNavigationGroup::Tools, 10),
            adminMenuItem('App\\Console', AdminNavigationGroup::Tools, 30),
        ),
        adminMenuGroup(
            AdminNavigationGroup::System,
            adminMenuItem('App\\Update', AdminNavigationGroup::System, 9999),
        ),
    ];
}
