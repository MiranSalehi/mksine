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

- No drag-and-drop in the front-end (it's only an editor convenience).
- No live preview by default (a separate preview route exists).
- No per-block ACL.
- No live Filament-ish UI per block — block configuration is a Filament form returned from `getSchema()`.
- Storage is a single JSON column. Page-level full-text search across blocks requires JSONB scans or denormalisation.

If you need a more sophisticated authoring tool, this is not the right primitive. If you need a flexible-but-bounded way to compose marketing pages, it works well.

## Concepts and rules

- **Type strings are persistent identifiers.** Don't rename them across versions. Namespace with your plugin id (e.g. `acme_pricing_table`) to avoid collisions.
- **Blocks must default missing keys** in their render view. Pages persisted with v1 schemas will be read with v2 code.
- **Containers come in two shapes**: simple children arrays vs. column buckets (used by `ColumnsComponent`). Pick one per block and document it.
- **Validation is opt-in.** `BuilderComponentInterface::validate()` exists but is **not called automatically**. Wire it from your save observer if you care.

## Where to read next

- [Concepts and feature flag](../guides/page-builder/concepts-and-feature-flag.md)
- [Creating a block](../guides/page-builder/creating-a-block.md)
- [Nesting](../guides/page-builder/nesting.md)
- [Validation](../guides/page-builder/validation.md)
- [Rendering](../guides/page-builder/rendering.md)
