---
title: Menu locations
---

# Menu locations

A **menu location** is a named slot in your theme — `header_primary`, `footer_links`, `mobile_drawer`. Editors assign one of their menus to each slot from the admin. The renderer then asks for "the menu at location X" and gets a tree.

This page covers how locations are registered and synced. The Menu Builder UI for editors is documented separately.

## Registration

Locations are registered via the `MenuLocationManager` singleton, usually from a service provider:

```php
use Miran\Mksine\Core\Hooks\MenuLocationManager;

public function boot(): void
{
    app(MenuLocationManager::class)->registerLocations([
        'header_primary' => __('mytheme::menus.header_primary'),
        'footer_links'   => __('mytheme::menus.footer_links'),
        'mobile_drawer'  => __('mytheme::menus.mobile_drawer'),
    ]);
}
```

The manager stores them in memory. They are written to the `menu_locations` table on demand (see [Sync](#sync)).

> **Naming.** Use snake_case keys with a stable prefix per package/theme. Renaming a key strands the editor’s assignment — the row in `menu_location_assignments` will reference a non-existent location row after sync. There is no auto-rename migration.

## Sync

```50:58:packages/mksine/src/Core/Hooks/MenuLocationManager.php
    public function syncToDatabase(): void
    {
        foreach ($this->locations as $key => $label) {
            MenuLocation::firstOrCreate(
                ['key' => $key],
                ['label' => $label]
            );
        }
    }
```

Two important properties:

1. **It only inserts.** Existing rows are never updated. Change a `label` in code? The DB still holds the old label.
2. **It never deletes.** Removing a location from your code leaves the row in the DB. Editors will continue to see it in the admin until you delete it manually.

The package calls `syncToDatabase()` from `ListMenuLocations::mount()` — i.e., **only when an admin opens the Menu Locations index page**. If you need locations available before that (e.g., in a seeder), call sync explicitly:

```php
app(\Miran\Mksine\Core\Hooks\MenuLocationManager::class)->syncToDatabase();
```

There is no Artisan command for this. There is no model observer. If you ship a theme that depends on a new location existing before any admin visits the locations page, you must:

- Call sync from your theme’s `boot()` (cheap; idempotent), **or**
- Add a seeder, **or**
- Use `MenuLocation::registerDefaults([...])` directly (the same shape as the seeder uses).

## Reading menus by location

Use the `MenuService` from your theme views:

```php
use Miran\Mksine\Services\MenuService;

$tree = app(MenuService::class)->forLocation('header_primary');

if ($tree === null) {
    // Editor hasn’t assigned a menu yet — render nothing or a fallback.
    return;
}

foreach ($tree['items'] as $item) {
    // …
}
```

`forLocation()` returns `null` when:

- The location doesn’t exist (typo or missing sync).
- The location exists but no menu is assigned.
- The assigned menu was deleted.

Treat `null` as an explicit "no menu" — never as an error. The theme should degrade gracefully (skip the nav bar, render a single home link, whatever fits).

## Honest limitations

- **No multi-menu per location.** A location holds at most one menu. If you need an A/B-tested header, that’s a custom theme concern.
- **No conditional locations.** Locations don’t support visibility rules ("show only when logged in"). Apply that in your theme template.
- **No per-page overrides.** A page cannot say "use this menu for header on this URL only". You can fake it by switching theme views per page type and looking up a different location key in each.
- **Labels in DB are not translatable.** `MenuLocation::label` is a plain string. If you need locale-specific labels, override the display in the resource’s `getEloquentQuery()` or call `__()` on the key in the admin UI.
- **The boot-time singleton is wiped per-request.** This is fine for read-side use because all data lives in the DB after sync, but it means you cannot inspect the in-memory list across requests.

## See also

- [Item sources](item-sources.md)
- Reference: [`MenuLocationManager`](../../reference/facades-and-managers.md#menulocationmanager), [`MenuService`](../../reference/facades-and-managers.md#menuservice)
