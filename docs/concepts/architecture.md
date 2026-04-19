---
title: Architecture
description: A 30-minute tour of how MKSine is put together.
order: 0
---

# Architecture

This page is a high-altitude tour. Read it once before diving into any specific guide so you know where things live and why.

## What MKSine is

A Laravel + Filament 4 package that turns a fresh Laravel app into a content/CMS-shaped admin without committing you to a particular feature set. It ships:

- A small set of **first-party resources** (Posts, Pages, Categories, Comments, Tags, Media, Menus, Users, Roles).
- A **plugin system** for shipping additional Filament resources, models, migrations, assets, and translations as composable units.
- A **hook system** for extending forms, tables, page header actions, and reactive events without forking.
- A **theme system** for the public-facing site, with view/asset/translation overrides per theme.
- An **opinionated page builder** (opt-in) for block-based page composition.
- Support tooling: menus, settings, localization, deployment archive.

What it deliberately is not:

- A multi-tenant SaaS framework. Multi-tenant is achievable on top, but it is not built in.
- A page-level cache layer. You bring caching in front (Cloudflare, Varnish, Laravel HTTP cache) per your needs.
- A search engine. It exposes hooks; bring Meilisearch / Algolia / Scout yourself.
- An e-commerce framework. The plugin and hook surfaces let you build one; nothing here ships out of the box.

## Architectural layers

```
┌─────────────────────────────────────────────────────────────────────┐
│  Host Laravel application (your project)                            │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  Plugins (your domain)                                      │    │
│  │  {plugin_root}/{id}/  →  src/, database/, resources/, ...   │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  Themes (your front-end)                                    │    │
│  │  themes/{id}/  →  views, assets, theme.json, theme.php      │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │  miran/mksine (this package)                                │    │
│  │  ┌──────────────────────────────────────────────────────┐   │    │
│  │  │ Filament resources & pages (admin UI)                │   │    │
│  │  │ Page builder, settings, media, menus, languages      │   │    │
│  │  └──────────────────────────────────────────────────────┘   │    │
│  │  ┌──────────────────────────────────────────────────────┐   │    │
│  │  │ Core/                                                │   │    │
│  │  │  Hooks (Event/Form/Table/Resource/Page managers)     │   │    │
│  │  │  Plugins (Manifest, Manager, Lifecycle, BootGuard)   │   │    │
│  │  │  Theme (Manager, Bootstrap, Registry, Enqueue)       │   │    │
│  │  │  PageBuilder (ComponentRegistry, BaseComponent)      │   │    │
│  │  │  Translation, Permalinks, MenuItemSources, …         │   │    │
│  │  └──────────────────────────────────────────────────────┘   │    │
│  │  ┌──────────────────────────────────────────────────────┐   │    │
│  │  │ Models & migrations: Page, Post, Media, Menu, …      │   │    │
│  │  └──────────────────────────────────────────────────────┘   │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  Laravel + Filament + Spatie Permission + Filament Shield + Livewire│
└─────────────────────────────────────────────────────────────────────┘
```

The package depends on Laravel and Filament 4. Plugins depend on the package. Themes are standalone and accessed through the package's theme system.

## Boot sequence (simplified)

When a request hits the application:

1. Laravel boots its service providers, including `MksineServiceProvider`.
2. The provider:
   - Loads `config/mksine.php` and merges it.
   - Optionally syncs `auth.providers.users.model` and `filament-shield.auth_provider_model` from `mksine.user_model`.
   - Registers manager singletons (`HookManager`, `FormHookManager`, `TableHookManager`, `ResourceHookManager`, `PageHookManager`, `MenuLocationManager`, `MenuItemSourceManager`, `SettingsTabManager`, `ComponentRegistry`, `ThemeManager`).
   - Registers core menu item sources, page builder blocks, theme Blade directives, model policies.
   - Registers the Filament panel via the package's `MksinePlugin`.
3. The plugin manager runs through `PluginAutoloader`, registering each active plugin's PSR-4 autoload prefix and discovering its assets.
4. Each plugin's `boot()` runs, after the package itself has booted. Plugins use this point to register hooks, page builder blocks, menu item sources, etc.
5. The active theme's `theme.php` is loaded by `ThemeBootstrap`, registering route overrides and any theme-specific hooks.

By the time a route is dispatched, the in-memory state of every manager reflects the union of package + plugins + theme.

## Persistence

State spans:

- **`config/mksine.php` and `.env`** — code-level configuration.
- **`mks_plugins`** — plugin lifecycle status (installed/active, last boot, boot error, version installed).
- **`mks_hooks`** — discovered hook listeners and their admin overrides (enabled/priority).
- **`settings`** — flat key/value with JSON support, written by the Settings page.
- **`menu_locations`, `menus`, `menu_items`, `menu_location_assignments`** — nav structure.
- **`media`, `media_attachments`** — media library and polymorphic attachments.
- **`pages.builder_payload`** — page builder JSON tree per page.
- Standard content models: `posts`, `pages`, `categories`, `comments`, `tags`, `users`, `roles`, `permissions` (the latter two via Spatie).

## Two systems you must understand

These two have a steeper learning curve than the rest because they cross-cut almost everything.

### Plugins
A plugin is a directory tree (default at `plugins/{id}/`, configurable via `mksine.plugins_path`) that the package discovers, autoloads, installs, activates, and boots. Each plugin has a manifest (`plugin.php`), an optional class implementing `PluginInterface`, and a lifecycle of install → activate → boot → deactivate → uninstall. The boot guard prevents a single failing plugin from crashing the application.

See [Plugins](plugins.md) and the [Plugin guide tree](../guides/plugins/golden-path.md).

### Hooks
Two families with different ergonomics:

- **Discovery hooks** (class-based, scanned by `mks:discover`): event listeners, form extensions, table extensions. Persisted in `mks_hooks`, can be toggled in admin.
- **Runtime hooks** (registered in `boot()` via the `Hooks::` facade): events, form/table extensions, resource relations and widgets, page header actions. In-memory; not persisted; not toggleable.

See [Hooks](hooks.md) and the [Hook guide tree](../guides/hooks/overview-two-families.md).

## Where customisation belongs

| Need                                         | Where to do it                                                                |
| -------------------------------------------- | ----------------------------------------------------------------------------- |
| Add an admin resource                        | Create a plugin with `mks-plugin:make-resource`                               |
| Extend an existing form/table                | A discovery hook listener (preferred) or `Hooks::extendForm/Table()` runtime  |
| React to a domain event                      | An event listener implementing `MksineListenerInterface`                      |
| Add a settings tab                           | `SettingsTabManager::registerTab()` from a plugin's `boot()`                  |
| Add a menu location                          | `MenuLocationManager::registerLocations()` + sync                             |
| Add a menu item source                       | Implement `MenuItemSourceInterface` and register it                            |
| Add a page builder block                     | Implement `BuilderComponentInterface` and `ComponentRegistry::register()`     |
| Override a theme view                        | Ship the same view path under your theme namespace                             |
| Add a translation namespace                  | Ship `lang/{locale}/*.php` under your plugin/theme; declare `translationsPath` |

If you find yourself wanting to fork the package to do something simple, that's a hint that an extension point is missing — open an issue or an ADR.

## Where it can break

- **Plugin boot errors** poison the request unless the boot guard catches them. Read [Lifecycle](../guides/plugins/lifecycle.md).
- **Hook performance** is your responsibility; there is no built-in slow-listener detection. See [Slow-hook logging](../guides/hooks/slow-hook-logging.md).
- **Theme view collisions** can be subtle when multiple themes ship the same view name; `ThemeBootstrap` resolves the active theme, but stale view caches mask the order.
- **`mks:release-archive`** ships only what its allowlist permits. Don't store user data in `public/` outside the allowlisted prefixes.

## See also

- [Plugins](plugins.md) — concept overview.
- [Hooks](hooks.md) — concept overview.
- [Themes](themes.md) — concept overview.
- [Page builder](page-builder.md) — concept overview.
- [Menus](menus.md) — concept overview.
- [Architecture decisions](../adr/) — recorded design decisions.
