---
title: Introduction
description: What MKSine is, who these docs are for, and the public-API stability model.
order: 0
---

# Introduction

`miran/mksine` is a Laravel + **Filament 4** content management foundation that adds:

- A **plugin system** (lifecycle, Filament autoloading, asset and translation publishing) — see [Plugin concepts](concepts/plugins.md).
- A **hook system** with two distinct families: discovery-based class listeners synced to a database table, and runtime/Blade extension via a static helper — see [Hook concepts](concepts/hooks.md).
- A **theme system** with view namespacing, layouts, and asset publishing — see [Theme concepts](concepts/themes.md).
- A **page builder** with typed component registration — see [Page builder concepts](concepts/page-builder.md).
- Filament-integrated **menus**, **media library**, **settings**, **permissions** (via Filament Shield), and **localization**.
- A **global geo catalogue** (countries, states, cities), store-wide geo settings, HTTP `/api/geo/*`, and import commands — see [Global geo system](guides/geo/overview.md).

These docs are the **canonical**, **package-portable** reference. They ship inside the package directory so consumers who only have the Composer installation read the same source as monorepo developers.

## Audience

These docs target **developers** who:

- Build plugins, themes, hooks, page-builder blocks, or menu sources for an MKSine installation.
- Integrate `miran/mksine` into a Laravel + Filament 4 application.
- Operate that application in production (deployment, release packaging, troubleshooting).

Out of scope:

- Admin UI walkthroughs for non-technical operators.
- Persian or other non-English translations of these docs.
- Generic Laravel or Filament tutorials that already exist upstream.

## Where to start

| Goal | Read |
|------|------|
| Install the package and bring up the panel | [Installation](01-installation.md) |
| See a working plugin in five minutes | [Quickstart](02-quickstart.md) |
| Understand the moving parts before writing code | [Concepts: Architecture](concepts/architecture.md) |
| Build a plugin end to end | [Plugin golden path](guides/plugins/golden-path.md) |
| Ship a hook listener | [Hook overview](guides/hooks/overview-two-families.md) |
| Add storefront admin bar items from a plugin | [Frontend admin bar](guides/storefront/frontend-admin-bar.md) |
| Set up multi-country addresses / checkout geo | [Global geo system](guides/geo/overview.md) |
| Look up an Artisan command or config key | [Commands](reference/commands.md), [Configuration](reference/configuration.md) |
| Diagnose a 404 or panel issue in production | [Operations: Troubleshooting](operations/troubleshooting.md) |

## What is public

Only the classes, interfaces, commands, and config keys listed in [API stability](reference/stability.md) are part of the public surface. Anything else is internal and may change between minor releases without notice. Commit to the public surface and you can rely on semver; reach into internals at your own risk.

## Documentation version

These docs describe the package revision you have installed. When upgrading, compare:

- [`packages/mksine/CHANGELOG.md`](../CHANGELOG.md) for breaking notes.
- The version in [`packages/mksine/composer.json`](../composer.json) and your application’s `composer.lock` entry for `miran/mksine`.

See [Versioning](meta/versioning.md) and [Upgrade guide](meta/upgrade-guide.md) for the full policy.

## Convention: `{plugin_root}`

Throughout these docs, **`{plugin_root}`** stands for `base_path(config('mksine.plugins_path'))`. The default published value of `mksine.plugins_path` is `plugins`, but it is overridable through `MKS_CMS_PLUGINS_PATH` or `config/mksine.php`. Published asset URLs use the fixed prefix `public/plugins/{id}/` — that path is implemented inside the package and is not configurable.

## Repository layout (for monorepo developers)

```text
packages/mksine/
  src/                  # PHP source, public and internal
  config/mksine.php     # default config (publishable)
  database/migrations/  # CMS tables
  resources/            # views, lang, assets shipped by the package
  routes/               # package routes (admin)
  docs/                 # this documentation tree
```
