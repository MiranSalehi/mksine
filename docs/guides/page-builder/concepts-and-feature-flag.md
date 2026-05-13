---
title: Page builder: concepts and feature flag
---

# Page builder: concepts and feature flag

The page builder is an opt-in feature that turns the `Page` model into a block-based editor. Pages can be **simple** (rich text in a `content` column) or **builder-driven** (a JSON tree of blocks in a `builder_payload` column). This page covers the conceptual model and the feature flag that gates everything.

## Feature flag

```env
MKSINE_FEATURE_PAGE_BUILDER=true
```

Or in `config/mksine.php`:

```php
'features' => [
    'page_builder' => env('MKSINE_FEATURE_PAGE_BUILDER', false),
],
```

When the flag is **off**:

- `Page` records still have a `builder_payload` column (it's part of the migration), but the form drops the **builder** option from the type select.
- The `PageBuilderField` is hidden in the resource.
- Existing `builder` pages still **render** (the theme template checks `usesBuilder()`); they just cannot be edited from the admin.

When the flag is **on**:

- The page form lets editors choose between `simple` and `builder` types.
- The `PageBuilderField` (a Filament Livewire component) appears for builder pages.
- The seeder, when running on a fresh install with `mksine.features.page_builder = true`, creates a page using the `mksine-default-home` template and the seeded blocks.

> The flag toggles **editing**, not rendering. Render paths always check `Page::usesBuilder()` and walk `builder_payload`. Disabling the flag does not erase data; it just hides the editor.

## Data model

A builder page stores its blocks in `pages.builder_payload` as JSON:

```json
[
  {
    "id": "block_64f0a1b2c3",
    "type": "heading",
    "data": { "text": "Welcome", "level": "h1", "alignment": "center" },
    "children": null
  },
  {
    "id": "block_64f0a1b2c4",
    "type": "columns",
    "data": { "columns": 2, "layout": "equal", "gap": "md" },
    "children": [
      { "id": "col_…", "items": [ /* nested blocks */ ] },
      { "id": "col_…", "items": [ /* nested blocks */ ] }
    ]
  }
]
```

Three invariants the rendering and editing code rely on:

1. Every block has a stable `id`. The editor uses it for keyed updates; the renderer uses it for `wire:key`.
2. Every block has a `type` that matches a key in the `ComponentRegistry`.
3. `data` shape matches `getDefaultData()` of that type. `children` is `null` for leaf blocks, an array of column buckets for `ColumnsComponent`, or an array of nested blocks for other container components.

If a block’s `type` is not in the registry, the renderer skips it. If `data` is missing fields, the Blade view should default them. **Don’t throw on bad shapes** — production pages may have stale block versions you no longer support.

## Two related concepts

These are distinct, do not confuse them:

| Concept           | Lives where                                               | Purpose                                          |
| ----------------- | --------------------------------------------------------- | ------------------------------------------------ |
| **Block**         | A class implementing `BuilderComponentInterface`          | Defines schema, default data, render view        |
| **Template**      | A class registered in `TemplateRegistry`                  | Pre-baked tree of blocks used to scaffold a page |

A template just emits a starter `builder_payload`. Once placed on a page, the editor sees individual blocks and can rearrange or delete them.

## Built-in blocks

The package registers a fixed set of blocks in `MksineServiceProvider::register()`:

- **Content**: `heading`, `text`, `feature_list`, `accordion`, `tabs`
- **Media**: `image`, `slider`
- **Layout**: `spacer`, `divider`, `columns`, `container_inset`, `hero`
- **Interactive**: `button`, `call_to_action`, `testimonial`
- **Sections** (theme-shipped marketing blocks): `mksine_post_comments_feed`, `mksine_testimonials_grid`, `mksine_clinic_features_grid`, `mksine_featured_domains`, `mksine_services_trio`, `mksine_finance_showcase`, `mksine_hero_domain`

You can register more from a plugin without modifying the package.

## Hooking new blocks from a plugin

In your plugin’s `boot()`:

```php
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;

public function boot(): void
{
    app(ComponentRegistry::class)->register(\Acme\MyPlugin\PageBuilder\PriceTableBlock::class);
}
```

The registry validates that the class implements `BuilderComponentInterface`; throws `InvalidArgumentException` otherwise. The block immediately appears in the picker on the next request.

There is no discovery step. There is no DB row. The registry is a runtime singleton — register on every boot.

## Honest scope

The page builder is **opinionated and limited** by design:

- Blocks are configured via a Filament form returned from `getSchema()`. You get whatever Filament can render — no custom JS UI per block.
- Storage is JSON inside one column. There is no normalised block table; you cannot SQL-query "all pages with a heading containing X" without a JSONB scan.
- There is no per-block ACL. If a user can edit a builder page, they can edit any block on it.
- There is no separate builder preview route; use the live themed page to verify layout.
- There is no draft/publish per block; the whole page is one record.

If you need a richer authoring tool, this is not the right primitive. For typical content sites it is fine.

## See also

- [Creating a block](creating-a-block.md)
- [Nesting](nesting.md)
- [Validation](validation.md)
- [Rendering](rendering.md)
- Reference: [`BuilderComponentInterface`](../../reference/contracts.md#buildercomponentinterface), [`ComponentRegistry`](../../reference/facades-and-managers.md#componentregistry)
