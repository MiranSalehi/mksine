---
title: Admin menu editor
description: Super-admin overrides for the admin sidebar, the navigation filter hook, and the stored format.
---

# Admin menu editor

**Settings → Admin menu** (`SettingsMenuPage`) lets a Super Admin change the admin sidebar without code:

- hide an item;
- move an item into another group;
- move an item into **Settings** (it becomes a link in the Settings sub-navigation);
- change an item's order or name;
- change a group's order or name, or hide the whole group.

Each row has a **Hide** / **Show** button for the common case; **Edit** opens the full form (placement, target group, order, name).

With nothing saved, the sidebar is exactly the menu the code defines. Every change can be undone with **Reset to default**, which deletes the setting.

## What the editor does not do

- **Hiding is not authorization.** Hidden and moved items keep their URL, and `canAccess()` still decides who can open them. Use Shield permissions to block access.
- **Moving to Settings does not change the page.** A Filament cluster is fixed in code and is part of the URL, so the page is not put into `SettingsCluster`. It is removed from the sidebar and linked from the Settings sub-navigation under **More tools**. Old bookmarks still work.
- **Settings and the editor itself cannot be hidden.** `SettingsCluster` and `SettingsMenuPage` can be moved, sorted and renamed, but the `hidden` and `settings` placements are ignored for them (`AdminMenuOverrides::PROTECTED_ITEM_KEYS`). If one of them is moved into a hidden group, the group stays with only that item.
- **Only the panel that uses `MksinePlugin` is affected.** Other panels use Filament's navigation as is.
- **No per-role menus yet.** One menu applies to every user. An item a user cannot access is still left out by Filament.

## How the sidebar is built

`MksinePlugin` binds `Miran\Mksine\Filament\Support\AdminMenu\MksineNavigationManager` as Filament's `NavigationManager`. On the admin panel, `get()` runs three steps:

1. Filament builds the navigation (`NavigationManager::get()`): every page and resource registers its items, and groups follow `AdminNavigationGroup`.
2. The `mksine.admin.navigation.groups` filter runs. Plugins change the menu here.
3. The stored super-admin overrides are applied (`AdminMenuOverrides`). They have the last word.

After step 3, empty groups are removed. The sidebar script then decides solo and flyout parents from what is actually rendered, so a group left with one item becomes a solo parent.

## Plugin hook: `mksine.admin.navigation.groups`

| | |
|---|---|
| Name | `mksine.admin.navigation.groups` (`MksineNavigationManager::GROUPS_FILTER`) |
| Value | `list<Filament\Navigation\NavigationGroup>` — groups in display order, top-level items first (a group with a blank label) |
| Extra argument | `Filament\Panel $panel` |
| Return | `list<NavigationGroup>` (anything that is not a `NavigationGroup` is dropped) |
| Runs | once per request, only on panels that use `MksinePlugin` |

Remove a plugin's items from the sidebar when a condition holds:

```php
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Filament\Support\AdminMenu\MksineNavigationManager;

Hooks::addFilter(MksineNavigationManager::GROUPS_FILTER, function (array $groups, Panel $panel): array {
    if (! acme_hides_store_items()) {
        return $groups;
    }

    $hidden = [
        \Acme\Accounting\Filament\Resources\LedgerResource::class,
    ];

    return array_map(
        fn (NavigationGroup $group): NavigationGroup => $group->items(
            collect($group->getItems())
                ->reject(fn (NavigationItem $item): bool => in_array($item->getKey(), $hidden, true))
                ->all(),
        ),
        $groups,
    );
});
```

Rules for listeners:

- Register from the plugin's `boot()`. The filter is in memory, like every runtime filter (see [Runtime registration](../hooks/runtime-registration.md)).
- Identify items by `NavigationItem::getKey()`, not by label. Labels are translated.
- Do not drop groups you did not add. Return them, possibly with fewer items; the manager removes groups that end up empty.
- Stored overrides run after your filter. If a Super Admin moved or renamed an item, that wins.
- What the filter returns is also what the editor lists. An item removed here cannot be configured there.

### Do not bind `NavigationManager` in a plugin

Filament resolves one `NavigationManager` per request. If a plugin calls `app()->scoped(NavigationManager::class, ...)` with its own subclass, one binding replaces the other: either the plugin's changes or the admin menu editor stop working, depending on boot order. Move that logic into the filter above.

## Keys

| What | Key |
|------|-----|
| Page or resource item | the class name, e.g. `Miran\Mksine\Filament\Pages\Languages` (Filament sets `->key(static::class)`) |
| Hand-made `NavigationItem` | whatever `->key(...)` sets. Without a key, Filament would fall back to the translated **label**, so the manager keys the item by its URL path instead: `url:/admin/products?tab=all`. That key changes if the URL changes, so set one when you can: `NavigationItem::make(...)->key(static::class . '@create')` |
| Group | an `AdminNavigationGroup` value: `media`, `content`, `orders`, `products`, `appearance`, `store_settings`, `users`, `access_control`, `tools`, `system`, `menus`, `theme_plugins` |

Groups that a plugin registers with its own enum or string cannot be renamed or reordered by the editor. Their items can still be moved into a core group.

## Stored format

One row in `settings`, key `mksine_admin_menu`, JSON value. A missing or empty row means no overrides.

```json
{
  "items": {
    "Miran\\Mksine\\Filament\\Pages\\Languages": { "placement": "settings" },
    "Miran\\Mksine\\Filament\\Pages\\ConsoleTerminal": { "placement": "group", "group": "system", "sort": 25, "label": "Terminal" },
    "Miran\\Mksine\\Filament\\Pages\\SystemUpdate": { "placement": "hidden" }
  },
  "groups": {
    "tools": { "sort": 90, "label": "Utilities" },
    "system": { "hidden": true }
  }
}
```

| Field | Values |
|-------|--------|
| `items.*.placement` | `default` (stay in the code's group), `hidden`, `group` (needs `group`), `settings` |
| `items.*.group` | an `AdminNavigationGroup` value; only read when `placement` is `group` |
| `items.*.sort` | integer, compared with the other items' `getNavigationSort()` in the same group |
| `items.*.label` | replacement label (not translated) |
| `groups.*.sort` | integer; a group without an override sorts at its enum position × 10 (`media` 10, `content` 20, … `tools` 90, `system` 100). Top-level items always stay first |
| `groups.*.label` | replacement label (not translated) |
| `groups.*.hidden` | `true` removes the group from the sidebar. Items are matched to groups after moves: an item moved out of a hidden group stays visible, an item moved into it is hidden. Only `true` is stored |

Behavior of the reader (`AdminMenuOverrides`):

- The setting is read at most once per request (scoped singleton) and re-read after a save.
- Unknown item keys (a removed class, an inactive plugin) are kept in the JSON but have no effect, so the change comes back when the plugin is activated again.
- Invalid placements, unknown group values and non-integer sorts are ignored. Broken JSON is treated as no overrides.
- Saving drops entries that equal the default. Saving only defaults deletes the row.
- The value must fit the `settings.value` column (8192 characters). The editor refuses a larger save and keeps the previous menu.

Reading it from code:

```php
use Miran\Mksine\Filament\Support\AdminMenu\AdminMenuOverrides;

$overrides = app(AdminMenuOverrides::class);

$overrides->itemOverride(\Miran\Mksine\Filament\Pages\Languages::class); // ['placement' => AdminMenuPlacement, 'group' => ?string, 'sort' => ?int, 'label' => ?string] or null
$overrides->save([...]);   // same shape as the JSON
$overrides->reset();
```

## Access

The page is visible to, and usable by, the Shield Super Admin role only (`config('filament-shield.super_admin.name')`), the same check as the console terminal and the updater. Save and reset check the role again on the server.

## Related

- [Runtime registration](../hooks/runtime-registration.md) — `Hooks::addFilter()` / `Hooks::filter()`
- [Adding settings tabs](adding-tabs.md) — how other settings are stored
- [Shield and policies](../auth/shield-and-policies.md)
