---
title: Menu item sources
---

# Menu item sources

A **menu item source** is a class that supplies items the editor can drag into a menu — a list of pages, posts, products, custom URLs, anything. The Menu Builder UI shows one tab per registered source.

Out of the box, the package registers four:

- `custom_link` — arbitrary URL + label (form-driven, no list)
- `category` — taxonomy categories
- `page` — published pages
- `post` — published posts

You can register more, replace existing ones, or remove them.

## Registering a source

Implement `MenuItemSourceInterface`. For sources backed by databases with many rows, also implement `MenuItemSourcePaginatedInterface` so the editor can search and paginate instead of loading everything.

```php
<?php

namespace Acme\Catalog\MenuItemSources;

use Acme\Catalog\Models\Product;
use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Models\MenuItem;

class ProductMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function getKey(): string  { return 'acme_product'; }
    public function getLabel(): string { return __('acme-catalog::menu.products'); }
    public function getIcon(): string  { return 'heroicon-o-shopping-bag'; }
    public function supportsMultipleSelection(): bool { return true; }

    public function getItems(): array
    {
        // Fallback for sources that don't implement pagination.
        // For paginated sources this is rarely called by the UI.
        return Product::query()->where('active', true)->limit(50)->get()
            ->map(fn ($p) => ['id' => $p->id, 'label' => $p->name, 'url' => "/p/{$p->slug}"])
            ->toArray();
    }

    public function getItemsPaginated(string $search, int $page, int $perPage): array
    {
        $q = Product::query()->where('active', true);
        if ($search !== '') {
            $q->where('name', 'like', "%{$search}%");
        }
        return [
            'items' => $q->offset(($page - 1) * $perPage)->limit($perPage)->get()
                ->map(fn ($p) => ['id' => $p->id, 'label' => $p->name, 'url' => "/p/{$p->slug}"])
                ->all(),
            'total' => $q->count(),
        ];
    }

    public function getItemsByIds(array $ids): array
    {
        return Product::query()->whereIn('id', $ids)->get()
            ->map(fn ($p) => ['id' => $p->id, 'label' => $p->name, 'url' => "/p/{$p->slug}"])
            ->keyBy('id')->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Product) {
            return [
                'type' => 'acme_product',
                'label' => $item->name,
                'url' => "/p/{$item->slug}",
                'reference_id' => $item->id,
            ];
        }

        return [
            'type' => 'acme_product',
            'label' => $item['label'] ?? '',
            'url' => $item['url'] ?? '',
            'reference_id' => $item['id'] ?? null,
        ];
    }

    public function getFormSchema(): ?array
    {
        return null; // Use default checkbox list rendered from getItemsPaginated.
    }
}
```

Register from your plugin or theme `boot()`:

```php
use Miran\Mksine\Core\Hooks\MenuItemSourceManager;

app(MenuItemSourceManager::class)->register(
    'acme_product',
    new \Acme\Catalog\MenuItemSources\ProductMenuItemSource(),
);
```

The key passed to `register()` and the value returned by `getKey()` should match. The first argument is what the editor stores; mismatching them silently corrupts persistence.

## Contract responsibilities

| Method                          | Required by                                 | Purpose                                                                                  |
| ------------------------------- | ------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `getKey()`                      | `MenuItemSourceInterface`                   | Stable identifier for persistence and registration.                                      |
| `getLabel()`                    | `MenuItemSourceInterface`                   | Human-readable tab title in the editor.                                                  |
| `getIcon()`                     | `MenuItemSourceInterface`                   | Heroicon name shown next to the tab.                                                     |
| `getItems()`                    | `MenuItemSourceInterface`                   | Full list. Used as fallback for non-paginated sources, and by some UI flows.             |
| `toMenuItem($item)`             | `MenuItemSourceInterface`                   | Converts a model **or** a raw row from `getItems*` into the persisted `MenuItem` shape.  |
| `getFormSchema()`               | `MenuItemSourceInterface`                   | Return Filament form components for custom-form sources (`custom_link`); else `null`.    |
| `supportsMultipleSelection()`   | `MenuItemSourceInterface`                   | If `true`, editor renders a multi-checkbox; otherwise single-add.                        |
| `getItemsPaginated($q,$p,$pp)`  | `MenuItemSourcePaginatedInterface` (optional) | Server-side search + pagination for large catalogues.                                  |
| `getItemsByIds($ids)`           | `MenuItemSourcePaginatedInterface` (optional) | Used when the editor confirms a selection; avoids loading the whole list a second time. |

`toMenuItem()` is called twice in the editor lifecycle: once when generating the picker rows, once when persisting. **Make it pure and cheap** — no DB writes, no event dispatches.

## When to implement the paginated interface

Implement it when:

- The list can grow beyond a few hundred rows.
- Users need to search by label.
- The list is multi-tenant or filtered per request and you don’t want to expose the full catalogue at once.

Skip it when:

- The list is bounded and small (e.g. up to ~50 entries).
- The label set is enumerable in code (e.g. enum-backed taxonomies).

If you only implement `getItems()`, the editor still works — it paginates and filters in memory. That works fine for small lists; it doesn’t scale.

## Replacing or removing built-in sources

The manager is a flat array keyed by source key:

```php
$manager = app(MenuItemSourceManager::class);

// Remove the built-in post source:
$manager->unregister('post');

// Replace it with a custom one (must use the same key):
$manager->register('post', new \Acme\Editorial\MenuItemSources\EditorialPostMenuItemSource());
```

The `MenuItemSourceManager` does **not** validate that the new source implements the interface — it stores whatever you pass it. You are responsible for keeping types correct.

Removing a source does not delete already-persisted menu items of that type. Items persisted with the removed key remain in the DB and continue to render via `MenuItem::url`. The editor just won’t know how to add new ones.

## Item shape persisted to `menu_items`

`toMenuItem()` returns:

```php
[
    'type'         => string,        // your getKey() (or a TYPE_* constant)
    'label'        => string,        // shown in the menu
    'url'          => string|null,   // resolved URL to render
    'reference_id' => int|null,      // FK to your underlying model, optional
]
```

`reference_id` is only meaningful to your source — the framework treats it as opaque. Use it to find the live record when rendering, e.g. to show "(unpublished)" badges.

## Honest limitations

- **No discovery.** Sources must be registered explicitly in `boot()`. There is no Artisan command, no DB seed, no auto-scan.
- **In-memory only.** The list of registered sources is built per request. There is no admin UI to enable/disable them — code is the source of truth.
- **No isolation.** A source that throws inside `getItemsPaginated()` will break the entire Menu Builder tab. The framework doesn’t guard against bad sources.
- **No permission filter on items.** Items returned by `getItemsPaginated()` are shown to the admin user regardless of policies on the underlying records. If you publish unpublished posts via `getItems()`, they’ll appear in the picker. Filter inside your source.
- **`toMenuItem()` is called once at persistence time**, not on every render. If your URL format changes later, the persisted `url` column does not update automatically. Either reconcile in a migration or rely on `reference_id` to re-derive the URL on render.

## See also

- [Locations](locations.md)
- Reference: [`MenuItemSourceInterface`](../../reference/contracts.md#menuitemsourceinterface), [`MenuItemSourcePaginatedInterface`](../../reference/contracts.md#menuitemsourcepaginatedinterface), [`MenuItemSourceManager`](../../reference/facades-and-managers.md#menuitemsourcemanager)
