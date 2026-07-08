---
title: Themes
description: How the public-facing theme system fits together.
order: 3
---

# Themes

Themes own the **public-facing** part of the site: the views and assets visitors see. The admin (Filament) is theme-independent.

## What a theme is

A directory under `themes/{id}/` with:

- `theme.json` — manifest (name, version, assets to publish).
- `theme.php` — optional bootstrap script for route overrides, custom hooks, view overrides.
- `resources/views/` — Blade templates, including page templates and layouts.
- `resources/lang/{locale}/*.php` — translations namespaced as `theme-{id}::file.key`.
- `resources/dist/` (or `public/`) — compiled CSS/JS published into `public/themes/{id}/`.
- `package.json` (optional) — `npm run build` wired for the package's release archive.

Only one theme is **active** at a time. The active theme is stored in `settings` and resolved by `ThemeManager` on boot.

## What the package gives you

- `ThemeManager` — discovers themes, picks the active one, publishes assets/translations.
- `ThemeBootstrap` — loads the active theme's `theme.php`, registers PSR-4 autoload for `themes/{id}/php/`, and applies route overrides registered through `ThemeRegistry`.
- `ThemeBladeDirectives` — `@themeAssets`, `@themeView`, `@themeDoAction`.
- `ThemeActionManager` — WordPress-style action hooks (`theme_add_action`, `theme_do_action`). Includes `layout.body_start` for the [frontend admin bar](../guides/storefront/frontend-admin-bar.md).
- `ThemeEnqueue` — request-scoped CSS/JS queue (`theme_enqueue_style`, `theme_enqueue_script`), rendered by `@themeAssets`.
- `ThemeRegistry` — per-page Livewire component overrides and route closures from `theme.php`.
- A storage-backed override layer for `dist/custom.css` and `dist/custom.js`, edited from the admin (data lives at `storage/app/theme-custom/{id}/`).

## How a request renders

1. The active theme is resolved.
2. The route hits a controller (or Livewire component) that ultimately yields the page model and a "page key" (e.g. `single_post`, `home`).
3. `theme_view($pageKey, [...])` looks up the theme's view and the package's fallback. The active theme wins if it provides the same view name.
4. The view renders. `@themeAssets` emits the queued CSS/JS, including any custom CSS/JS from storage. Blade directives like `@themeDoAction('page.before_content')` invoke the action manager.
5. Page builder content (if the page is a builder page) is rendered through the package dispatcher (`mksine::page-builder.render.block`).

## What "active theme wins" means in practice

- The same Blade name (e.g. `themes::layouts.app`) resolves to the active theme's file if it provides one.
- Translations follow the namespace convention; the active theme's strings override those of inactive ones.
- Plugin-published frontend views (under their own namespace) are unaffected by theme switching unless the theme overrides them by name.

## What themes are not

- Themes don’t modify the admin. If you want to skin the admin, configure the Filament panel instead.
- Themes don’t bundle business logic. They bundle presentation. Any non-trivial logic should live in a plugin.
- Themes don’t auto-extend forms/tables. Use plugins (or runtime hooks from `theme.php`).

## Switching themes

Switching is non-destructive. The previous theme's data (custom CSS/JS in storage, settings) is preserved. Re-activating restores it. There is no migration cost beyond clearing view/route caches.

## Where to read next

- [Creating a theme](../guides/themes/creating-a-theme.md)
- [Views and layouts](../guides/themes/views-and-layouts.md)
- [Assets and publish](../guides/themes/assets-and-publish.md)
- [Translations](../guides/themes/translations.md)
- [Custom asset storage](../guides/themes/custom-asset-storage.md)
- [Frontend admin bar](../guides/storefront/frontend-admin-bar.md)
