---
title: Nesting blocks
---

# Nesting blocks

Nesting is how the page builder composes layouts. A block is a "container" if `supportsChildren()` returns `true`. Containers store nested content under a `children` key. There are two distinct nesting shapes — get this wrong and the editor will silently corrupt the payload.

## The two shapes

### Shape A: simple container

`children` is an array of blocks. Used by everything except `ColumnsComponent`.

```json
{
  "id": "block_a",
  "type": "container_inset",
  "data": { "padding_inline": "md", "max_width": "5xl" },
  "children": [
    { "id": "block_b", "type": "heading", "data": {…}, "children": null }
  ]
}
```

This is what `ContainerInsetComponent` returns from `createInstance()`. Note the JSON above is **simplified** — the actual structure used by `ContainerInsetComponent` mirrors the columns shape (one bucket containing `items`) so the editor can render a single drop region. Read the source if you need bit-perfect detail.

### Shape B: column buckets

`children` is an array of column objects, each with its own `id` and `items` array. Used by `ColumnsComponent`.

```json
{
  "id": "block_columns",
  "type": "columns",
  "data": { "columns": 2, "layout": "equal", "gap": "md" },
  "children": [
    { "id": "col_…", "items": [ {…}, {…} ] },
    { "id": "col_…", "items": [ {…} ] }
  ]
}
```

Why two shapes? Columns need stable identifiers per region so the editor can target a drop into "column 2" without renumbering on every change. Other containers only have one drop region, so a flat array is enough — but for consistency with the editor's drag-and-drop model, container blocks ship with a single column bucket too.

> **Pick one and document it on your block.** If you build a custom container, decide upfront whether it has multiple regions (use buckets) or one (use a single bucket). Don't switch later — existing pages won't migrate.

## Authoring a container block

```php
final class TwoColumnHeroBlock extends BaseBuilderComponent
{
    public static function getType(): string { return 'acme_two_col_hero'; }
    public static function getName(): string { return __('acme::builder.two_col_hero'); }
    public static function getCategory(): string { return self::CATEGORY_LAYOUT; }
    public static function getSchema(): array { return [/* … */]; }
    public static function getDefaultData(): array { return ['theme' => 'light']; }

    public static function supportsChildren(): bool { return true; }
    public static function getMaxChildren(): ?int { return 2; }

    public static function getBuilderChildRegionLabel(int $i, int $count): string
    {
        return $i === 0
            ? __('acme::builder.two_col_hero.left')
            : __('acme::builder.two_col_hero.right');
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $instance['children'] = [
            ['id' => uniqid('col_'), 'items' => []],
            ['id' => uniqid('col_'), 'items' => []],
        ];
        return $instance;
    }
}
```

Key points:

- Override `createInstance()` to seed the column buckets when the block is added. Without this, `parent::createInstance()` returns `children: []` and the editor renders no drop regions.
- `getMaxChildren()` is enforced by the editor as a soft cap; the renderer does not check it.
- `getBuilderChildRegionLabel()` is what the editor shows above each region.

## Rendering nested blocks

The recursion lives in the package view:

```17:21:packages/mksine/resources/views/page-builder/render/block.blade.php
@if ($viewName !== '' && \Illuminate\Support\Facades\View::exists($viewName))
    @include($viewName, $includeData)
@else
    {{-- Unknown component or missing view --}}
```

In your container's render view, walk the children and `@include` the dispatcher:

```blade
@php
    $children = $children ?? [];
@endphp

<div class="acme-two-col-hero grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach ($children as $colIndex => $bucket)
        <div data-region="{{ $colIndex }}">
            @foreach ($bucket['items'] ?? [] as $item)
                @include('mksine::page-builder.render.block', ['block' => $item])
            @endforeach
        </div>
    @endforeach
</div>
```

The `block.blade.php` partial is **the only place** that knows how to look up a block's render view. Always go through it; never call your own block's view directly when rendering children — you'd skip the registry lookup and break custom blocks others nest inside yours.

## Limits and gotchas

- **No depth limit is enforced.** A block can be nested inside another container indefinitely. Realistically, after 3–4 levels the editor becomes unusable; consider hard-capping in your own UX with a custom validator.
- **Children survive even when the container’s schema changes.** If you remove `supportsChildren()` from a block in a later release, existing pages with children rendered through that block will still have those children in `builder_payload`. They just stop rendering. Plan a migration if you change this flag.
- **No referential integrity.** A child block referencing a deleted post or media id is your problem to handle in the render view.
- **The editor does not deep-clone payloads on duplicate.** Duplicating a container regenerates the top-level `id` but reuses child `id`s. If your block uses `id` to key DOM state on the front-end, you'll get duplicate keys after a duplicate-and-paste.

## See also

- [Concepts and feature flag](concepts-and-feature-flag.md)
- [Creating a block](creating-a-block.md)
- [Validation](validation.md)
- [Rendering](rendering.md)
