---
title: Menus
description: Locations, menus, items, and item sources.
order: 5
---

# Menus

The menu system has four moving parts. Get the vocabulary right and the rest of the system makes sense.

## The four entities

| Entity                       | Stored in                       | Owns                                                                  |
| ---------------------------- | ------------------------------- | --------------------------------------------------------------------- |
| **Menu location**            | `menu_locations`                | A named slot the theme renders into (`header_primary`, `footer_links`). |
| **Menu**                     | `menus`                         | A named tree of items (`Main navigation`, `Footer about`).             |
| **Menu item**                | `menu_items`                    | A row in a menu's tree (label, URL, parent, order, type).              |
| **Menu location assignment** | `menu_location_assignments`     | The link between a menu and a location.                                 |

A location holds at most one menu at a time. A menu can be assigned to many locations.

## Item sources

Editors don't type URLs by hand for every item. **Item sources** supply lists of pickable items: pages, posts, categories, custom links, anything you implement.

The package ships four:

- `custom_link` — arbitrary URL + label.
- `category` — categories taxonomy.
- `page` — published pages.
- `post` — published posts.

Plugins can register more by implementing `MenuItemSourceInterface` (or `MenuItemSourcePaginatedInterface` for large catalogues with search).

## How rendering works

A theme view asks for a tree by location key:

```php
$tree = app(\Miran\Mksine\Services\MenuService::class)->forLocation('header_primary');
```

Returns either `null` (no location, or no menu assigned) or an array with `id`, `name`, `slug`, and a nested `items` tree.

The rendering of the markup is the theme's job. The package gives you the data; you decide how it looks.

## Registration model

Menu **locations** are registered in code via `MenuLocationManager::registerLocations()`. The in-memory list is **synced to the DB** lazily — only when an admin opens the locations page or you call `syncToDatabase()` explicitly. This means locations declared in code may not exist in the DB on a fresh install until something triggers the sync.

Item **sources** are registered the same way (via `MenuItemSourceManager::register()`), but they don't persist anywhere. The list is rebuilt on every boot.

## What the system doesn't do

- No per-page menu overrides ("show this menu on this URL only"). Branch in your theme.
- No multi-menu per location ("A/B test the header"). One menu per location, period.
- No conditional items by default ("hide if logged out"). Filter in your theme.
- No menu permissions per item. If the user can render the page, they see all items.

## Where to read next

- [Locations](../guides/menus/locations.md)
- [Item sources](../guides/menus/item-sources.md)
- Reference: [`MenuLocationManager`](../reference/facades-and-managers.md#menulocationmanager), [`MenuItemSourceManager`](../reference/facades-and-managers.md#menuitemsourcemanager), [`MenuItemSourceInterface`](../reference/contracts.md#menuitemsourceinterface)
