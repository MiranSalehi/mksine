---
title: Upgrade guide
description: Per-release migration notes for miran/mksine.
order: 1
---

# Upgrade guide

This file accumulates **migration notes** per release. Add a new section at the top whenever a release changes anything visible in [API stability](../reference/stability.md) (interfaces, commands, config keys, default behavior). Cross-link to `CHANGELOG.md`.

If a release contains only patch-level fixes with no migration steps, you do not need a section here.

## Template

When adding an entry, copy this skeleton:

```markdown
## X.Y.Z (YYYY-MM-DD)

### Breaking
- One-line summary. Migration: ...

### Deprecated
- One-line summary. Replacement: ... Removal target: vX+1.0.0.

### Behavior changes (non-breaking, but visible)
- One-line summary.
```

---

## Unreleased

### Breaking

- None yet.

### Deprecated

- `HookManager::enableListener()`, `HookManager::disableListener()`, `HookManager::setPriority()` — superseded by direct DB updates against `mks_hooks` (or the admin Hooks page). Removal target: `v2.0.0`.
- `MksineEvent::cancel()` is documented but not used by any first-party listener. Treat the cancellation state as advisory; the dispatcher does not abort once entered. Decision will be made by `v1.1.0` whether to enforce or remove.

### Behavior changes (non-breaking, but visible)

- Documentation tree restructured under `packages/mksine/docs/`. Old paths (`10-plugin-golden-path.md`, `40-security-auth.md`, etc.) have moved into topic directories; see `_nav.yml`. Internal links inside the package now use the new tree.
- Plugin source path is referenced as `{plugin_root}` (= `base_path(config('mksine.plugins_path'))`) throughout the docs. The default value is unchanged (`plugins`).
- `mksine.hooks.log_slow_hooks` and `mksine.hooks.slow_hook_threshold` are documented as **configured but not yet honoured** by `HookDispatcher`. No removal planned; implementation pending. See [Slow-hook logging](../guides/hooks/slow-hook-logging.md).
- `mksine.hooks.cache_discovery` is documented as **configured but not yet honoured** by `DiscoverHooksCommand`. Same status.
- `TableHookManager` `extend*` methods accept and return the full `Table` object (not arrays of components). Older docstrings implied otherwise; the implementation has always taken `Table`. Inline PHPDoc was updated for clarity.
- `TableHookManager::apply()` does **not** catch exceptions raised by registered callbacks (unlike `FormHookManager::apply()`, which logs and continues). This asymmetry is intentional but now explicitly documented.
- The `ComponentRegistry::validateComponent()` exists but is **not** called automatically anywhere in the framework. Plugin authors who rely on `BuilderComponent::validate()` must invoke it themselves at save time. See [Validation](../guides/page-builder/validation.md).
- `MenuLocationManager::syncToDatabase()` only inserts new locations; it never updates `label` on existing rows or deletes removed locations. Document this whenever you change a location’s label in code.
- Page builder docs introduce the `{plugin_root}/{plugin_id}` convention for examples and stop referencing client-specific plugin IDs.

### New

- Documentation: full guide tree for plugins, hooks, themes, page builder, menus, media, settings, localization, auth.
- Documentation: deep dive on `mks:release-archive` covering build root discovery, the `public/` allowlist, and verification steps.
- Documentation: per-area troubleshooting and validation checklist sections expanded to cover menus, settings, translations, and media.
- Documentation: every page now carries YAML front matter (`title:` required) for SSG adapters (VitePress, Docusaurus, Starlight, Mintlify).
- Tooling: `php scripts/lint-docs.php` (and the matching `composer lint:docs` script and `tests/DocsNavTest.php` Pest suite) enforces that every Markdown page is in `_nav.yml` exactly once, that every nav entry exists on disk, and that every page has a non-empty `title:` in its front matter. Wired into a `.github/workflows/docs-lint.yml` workflow that runs on every PR touching `docs/`.

## 1.0.0 — initial release placeholder

The current `CHANGELOG.md` lists `1.0.0 — 202X-XX-XX` as a placeholder. When 1.0.0 actually ships:

- Move every Unreleased entry above into a `## 1.0.0 (date)` section.
- Confirm [API stability](../reference/stability.md) reflects the surface that ships.
- Link from the corresponding `CHANGELOG.md` line back to this guide.
