<div align="center">

# MKSine

**The Laravel + Filament 4 CMS foundation — build content sites, page builders, and plugin ecosystems without fighting your admin panel.**

<!-- ⬇️ PLACEHOLDER: hero GIF — page builder in action (drag block, edit, save, see it live) -->
<!-- ![MKSine Page Builder Demo](./media/hero-pagebuilder.gif) -->

[![Latest Version](https://img.shields.io/packagist/v/miran/mksine.svg?style=flat-square)](https://packagist.org/packages/miran/mksine)
[![Total Downloads](https://img.shields.io/packagist/dt/miran/mksine.svg?style=flat-square)](https://packagist.org/packages/miran/mksine)
[![License](https://img.shields.io/github/license/MiranSalehi/mksine.svg?style=flat-square)](LICENSE)
<!-- Add a GitHub stars badge once you have a few: -->
<!-- [![Stars](https://img.shields.io/github/stars/MiranSalehi/mksine.svg?style=flat-square)](https://github.com/MiranSalehi/mksine/stargazers) -->

[Quick Install](#quick-install) · [Features](#features) · [Documentation](https://github.com/MiranSalehi/mksine/tree/main/docs)
<!-- · [Live Demo](#) — add once ready -->

</div>

---

## Why MKSine

Filament gives you a great admin panel. MKSine gives you the **CMS layer on top of it**: content types, a visual page builder, installable plugins with real lifecycle management, themes, menus, hooks, and a settings system — all built the Filament way, so it feels native instead of bolted on.

If you've ever built a "simple site" in Laravel and ended up hand-rolling pages, blocks, menus, and a plugin system from scratch — this is that groundwork, already done.

---

## Quick install

```bash
composer require miran/mksine
php artisan filament:install --panels
# Register MksinePlugin in AdminPanelProvider (remove default Dashboard page)
php artisan mksine:install --migrate
php artisan mksine:create-super-admin
```

Full installation guide → [docs/01-installation.md](https://github.com/MiranSalehi/mksine/blob/main/docs/01-installation.md)

---

## Features

### 🧱 Page Builder
Opt-in visual block editor for marketing pages. Compose typed blocks (hero, columns, CTA, and more) via drag-and-drop; the tree is stored as JSON and rendered by the active theme.

<!-- ⬇️ PLACEHOLDER: pagebuilder.gif -->
<!-- ![Page Builder](./media/pagebuilder.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/NhYtXa5MAwrZ5vHdXpK9Jsk6DqQZt7qv9qB79ThB.gif)

📖 [Concepts](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/page-builder.md) · [Creating a block](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/page-builder/creating-a-block.md) · [Rendering](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/page-builder/rendering.md)

---

### 🧩 Plugins
Drop-in extensions under `plugins/{id}/` with full lifecycle — install, activate, deactivate — ZIP upload, filesystem discovery, Filament autoloading, migrations, assets, and hook listeners. No forking core to extend the system.

<!-- ⬇️ PLACEHOLDER: plugins.gif or screenshot — upload ZIP → discover → activate flow -->
<!-- ![Plugins](./media/plugins.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/JKzegtwi6hfxLBqBOVKk4c0AkUQMXGqG68iMyQlO.png)!
📖 [Concepts](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/plugins.md) · [Build a plugin (golden path)](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/plugins/golden-path.md) · [Lifecycle](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/plugins/lifecycle.md)

---

### 🎨 Themes
Package themes and project themes (`themes/{id}/`). One active theme controls storefront Blade views and published CSS/JS — upload, discover, activate, and edit custom CSS/JS directly from the panel.

<!-- ⬇️ PLACEHOLDER: themes.gif or screenshot -->
<!-- ![Themes](./media/themes.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/Q2d83kGDJMSHCaCeIH7apsbOVV4n9B81C7SqmjOO.png)
📖 [Concepts](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/themes.md) · [Creating a theme](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/themes/creating-a-theme.md)

---

### 🧭 Menus
Visual menu builder: drag to reorder, nest items, add from pages, posts, categories, or custom links. Assign menus to theme locations like header and footer.

<!-- ⬇️ PLACEHOLDER: menus.gif -->
<!-- ![Menus](./media/menus.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/BxMUjjwMxXoj3fYQUnpCNO04bbXfiTAtT4oZsKKz.gif)
📖 [Concepts](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/menus.md) · [Locations](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/menus/locations.md)

---

### 🪝 Hooks
Two families: **discovery hooks** (class listeners synced to DB, toggleable in admin) and **runtime hooks** (closures in `boot()`). Extend forms, tables, events, and Filament resources — without touching core.

📖 [Concepts](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/hooks.md) · [Two families overview](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/hooks/overview-two-families.md)

---

### 📁 Content & Media
Manage pages, posts, categories, and moderated comments from the Filament admin. A central media library handles files with image optimization, thumbnails, and a `MediaPicker` for forms — attach files polymorphically to any model.

<!-- ⬇️ PLACEHOLDER: content.gif or screenshot — content list / editor -->
<!-- ![Content](./media/content.gif) -->
<!-- ⬇️ PLACEHOLDER: media-library.gif or screenshot -->
<!-- ![Media Library](./media/media-library.gif) -->

📖 [Architecture](https://github.com/MiranSalehi/mksine/blob/main/docs/concepts/architecture.md) · [Media library guide](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/media/library.md)

---

### ⚙️ Settings
Site-wide settings with core tabs (General, Permalinks, Geo) plus plugin tabs registered dynamically via `SettingsTabManager` — no editing the settings page class required.

<!-- ⬇️ PLACEHOLDER: settings.gif or screenshot -->
<!-- ![Settings](./media/settings.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/vxjytqwXflokpvVnujnfIPTcTZsRRW8i9sdRZWcy.png)
📖 [Adding settings tabs](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/settings/adding-tabs.md)

---

### 🌐 Translations
Edit application, plugin, and theme translation files directly from the admin — pick language, source, and file; saving writes to the source and vendor publish path.

<!-- ⬇️ PLACEHOLDER: translations.gif or screenshot -->
<!-- ![Translations](./media/translations.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/ffzAJoUz1fTplBA3HdWYm74PL75S6Xsx8XkJYQzJ.png)
📖 [Translations workflow](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/localization/translations.md)

---

### 💻 Admin Console
Super Admin only. Run allowed `php artisan …` and `composer …` commands from the panel — live output, background workers, and full command history.

<!-- ⬇️ PLACEHOLDER: admin-console.gif or screenshot -->
<!-- ![Admin Console](./media/admin-console.gif) -->
![](https://fls-a148a526-7ce4-465e-b283-9b405912858a.laravel.cloud/plugins/documentation/images/Qbx9Cmu5x7F1hXp0lu7KcJYHZsunsNBgMJ1qretm.png)
📖 [Commands reference](https://github.com/MiranSalehi/mksine/blob/main/docs/reference/commands.md)

---

### 🔐 Auth & permissions
Filament Shield integration, a super admin role, and optional user subclass requirements for plugins that need them.

📖 [User subclass](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/auth/user-subclass.md) · [Shield and policies](https://github.com/MiranSalehi/mksine/blob/main/docs/guides/auth/shield-and-policies.md)

---

## Operations & deployment

Deployment guides, release ZIP packaging, ZIP-based updates, and troubleshooting — all documented for production use, not just local dev.

📖 [Deployment](https://github.com/MiranSalehi/mksine/blob/main/docs/operations/deployment-hosting.md) · [Troubleshooting](https://github.com/MiranSalehi/mksine/blob/main/docs/operations/troubleshooting.md) · [Upgrade guide](https://github.com/MiranSalehi/mksine/blob/main/docs/meta/upgrade-guide.md)

---

## Full documentation

This overview covers the surface. For architecture decisions, guides, and reference docs, see the full index:

👉 **https://github.com/MiranSalehi/mksine/tree/main/docs**

---

<div align="center">

If MKSine saves you time, a ⭐ on the repo helps other Laravel/Filament developers find it.

</div>