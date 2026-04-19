---
title: Documentation SLO
description: Audience and success criteria for the MKSine documentation.
order: 3
---

# Documentation SLO

This document fixes **who the docs are for** and what “done” means. Keep it aligned with [Introduction](../00-introduction.md), [Validation checklist](../operations/validation-checklist.md), and [Versioning](versioning.md).

## Primary audiences

| Audience | Typical context | What they need |
|----------|-----------------|----------------|
| **Host app team** | Monorepo or client project with `miran/mksine` | Installation, golden path, commands reference, deploy without npm on server |
| **Third-party / partner developers** | Same, often without Cursor skills | These docs inside the package (`packages/mksine/docs/`) as **canonical** source |
| **AI-assisted authors** | Cursor, CLI agents, RAG over repo | Small markdown files under `docs/`, explicit contracts (hooks, discovery paths, configuration table) |

Packagist consumers receive **this package**; they do **not** receive `.cursor/skills/`. Skills in any host repo must **link here**, not duplicate long prose.

## Service-level objective for plugin development

**Goal:** a developer who has never read MKSine core PHP can ship a **minimal CRUD plugin** by following [Quickstart](../02-quickstart.md) and [Plugin golden path](../guides/plugins/golden-path.md) only.

**Concrete outcomes:**

1. `php artisan mks-plugin:discover` sees the plugin.
2. `mks-plugin:install` and `mks-plugin:activate` succeed; no boot failure recorded in `mks_plugins`.
3. At least one Filament resource appears in the admin panel (with appropriate permissions).
4. Migrations apply via `php artisan mks-plugin:migrate {id}` (or full app migrate, per project).
5. Built assets and lang (if used) are committed or documented for deploy: `public/plugins/{id}/`, `lang/vendor/{id}/`.

**Non-goals for the golden path:** large product domains, vendor ZIP publishing as a core command, custom auth stacks beyond what [User subclass](../guides/auth/user-subclass.md) describes. Cover those with your own plugin docs and team conventions.

## SLO for hooks

A developer can register a discovery-based listener and a runtime listener using only [Hook overview](../guides/hooks/overview-two-families.md), one per-family guide, and [discovery paths](../guides/hooks/discovery-paths.md). Failure modes are diagnosable via [Troubleshooting](../operations/troubleshooting.md).

## SLO for themes

A developer can scaffold a theme, customize a layout, publish assets, and switch the active theme using only [Theme guides](../guides/themes/creating-a-theme.md). Theme management feature flag and asset storage options are documented in `guides/themes/`.

## Version coupling

- **Docs and code:** treat incompatible changes to `plugin.php` contract, Artisan signatures, or hook discovery as **semver-minor or major** in `CHANGELOG.md` and call them out in [Upgrade guide](upgrade-guide.md).
- **Packagist / lock file:** the behavior described here must match the **`miran/mksine` version pinned in `composer.lock`**. After `composer update miran/mksine`, re-read [Introduction → Documentation version](../00-introduction.md#documentation-version) and `CHANGELOG.md` for breaking notes.
- **Cursor skills:** after editing canonical docs, trim skills to summaries plus links so drift is visible in review (skills cannot version themselves per package release).

## Documentation contribution SLO

Every guide page must include, in order:

1. **What and when** — one-paragraph use-case statement.
2. **Concepts** — diagram or labeled list of moving parts.
3. **End-to-end working example** — a concrete code block someone can copy.
4. **Reference table** — methods/options/keys it touches, linking to the corresponding `reference/` page.
5. **Edge cases / gotchas** — at least three real ones.
6. **See also** — cross-links.

Reference pages list **every** signature, key, or option found in the inventory; no `(...)` shortcuts. See [Contributing](contributing.md) for the writing checklist.
