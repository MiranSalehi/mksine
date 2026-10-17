---
title: Page builder
description: Block-based page composition, opt-in.
order: 4
---

# Page builder

The page builder is an opt-in feature (`mksine.features.page_builder`). When on, the `Page` resource lets editors choose between a **simple** rich-text page or a **builder** page composed of typed blocks stored as JSON in `pages.builder_payload`.

## Mental model

A block is:

- A class implementing `BuilderComponentInterface` (typically via `BaseBuilderComponent`).
- A Blade view that renders it.

The framework keeps a `ComponentRegistry` (in-memory, populated each boot from the package and any plugin/theme `register()` calls). Editors pick blocks from the picker; the picker shows everything in the registry, grouped by category.

The bundled MKSine landing blocks (`mksine_*`) and the `mksine-default-home` template are not in that core list. `MksineServiceProvider` does not register them. `themes/mksine/theme.php` calls `RegisterMksineThemeBlocks` when that theme boots. `PayloadNormalizer` drops any type that is not registered, including from the editor preview. A host that keeps those sections while another theme is active must call `RegisterMksineThemeBlocks::register()` on its own boot. This application does that from `App\Providers\AppServiceProvider`.

A page's blocks are persisted as a tree of:

```json
[
  { "id": "block_…", "type": "heading", "data": {…}, "children": null },
  { "id": "block_…", "type": "columns", "data": {…}, "children": [ …column buckets… ] }
]
```

There is no separate block table. The full tree lives in one column.

## Why opt-in

Some MKSine installations don't want a block editor. The feature flag keeps the editor and the field hidden, but render paths still respect existing `builder_payload` data — so disabling the flag doesn't break previously-built pages.

## Honest scope

The page builder is intentionally minimal:

- The editor canvas is a signed iframe of the unsaved payload. It uses the same `render.block` loop as the theme page. Guests cannot open that URL. An empty page does not mount the iframe; it shows one add row. There is no structure list and no left navigator. Insert, move, and remove live on the canvas. See [Editor](../guides/page-builder/editor.md).
- Block configuration is a Filament form returned from `getSchema()`.
- Storage is a single JSON column. Page-level full-text search across blocks requires JSONB scans or denormalisation.

If you need a more sophisticated authoring tool, this is not the right primitive. If you need a flexible-but-bounded way to compose marketing pages, it works well.

## Concepts and rules

- **Type strings are persistent identifiers.** Don't rename them across versions. Namespace with your plugin id (e.g. `acme_pricing_table`) to avoid collisions.
- **Blocks must default missing keys** in their render view. Pages persisted with v1 schemas will be read with v2 code.
- **Containers come in two shapes**: simple children arrays vs. column buckets (used by `ColumnsComponent`). Pick one per block and document it.
- **Validation runs on the editor save path.** `PayloadNormalizer` calls `validate()` for paste, the block modal, template load, and page duplicate. Imports that write `builder_payload` directly still have to call the normalizer themselves. Front-end render also allowlists heading tags and link protocols, so an old payload cannot choose a tag name or a `javascript:` URL.
- **Blocks can carry a shared `presentation` object** (`anchor`, `spacing`, `visibility`, `padding`, `background`, `align`, `width`). Values outside the allowlist are dropped. It is not part of each component's `getSchema()`. The renderer wraps every block with it. Old payloads without the newer keys render with no extra classes.
- **A `section_ref` block stores a section id, not a copy of the section tree.** `page_builder_sections` is the record. Rendering reads it at request time. A section cannot contain a reference to itself. Detach is the only deep copy.
- **Heading text can be edited from the canvas preview.** The rich-text block stays in the modal.
- **Builder saves keep up to 20 `page_builder_revisions` rows.** A row is written when the saved `builder_payload` changes, including the payload that was on the page before the first change. Editor undo stays in the session and is not a revision.

## Where to read next

- [Concepts and feature flag](../guides/page-builder/concepts-and-feature-flag.md)
- [Editor](../guides/page-builder/editor.md)
- [Creating a block](../guides/page-builder/creating-a-block.md)
- [Nesting](../guides/page-builder/nesting.md)
- [Validation](../guides/page-builder/validation.md)
- [Rendering](../guides/page-builder/rendering.md)
- [Headless page document](../guides/page-builder/headless.md) — JSON read API for React, Vue, Angular, and Next.js
