---
title: Creating a theme
---

# Creating a theme

A theme in MKSine is a directory under `resources/views/themes/{identifier}/` (project theme) or shipped inside the package at `resources/views/themes/{identifier}/` (package theme). It contains:

- A `theme.json` manifest.
- One or more Blade templates (`home.blade.php`, `single.blade.php`, etc.) and an entry layout (`layouts/index.blade.php`).
- A `dist/` directory of compiled assets (CSS/JS) — **committed to the repo**, not a node-only build artefact.
- Optional `src/` for the original source files (Tailwind, esbuild, fonts).
- Optional `theme.php` and `php/` for backend overrides and custom routes.
- Optional `resources/lang/` for translations.

This guide walks the scaffold end-to-end. For deeper details on the runtime contract, see the rest of the theme guides linked at the end.

## Scaffold

```bash
php artisan mks:make-theme "Stellar"
```

Options:

| Flag                    | Meaning                                        |
| ----------------------- | ---------------------------------------------- |
| `--identifier=stellar`  | Override the slugified identifier              |
| `--author="…"`          | Author name written into `theme.json`          |
| `--description="…"`     | Description for `theme.json` and the picker    |
| `--force`               | Overwrite an existing directory; also implicitly answers "yes" to the prompt for `theme.php` + `php/` overrides |

The command creates the full skeleton under `resources/views/themes/stellar/` and prints a summary of every file written.

## Layout of a fresh theme

```
resources/views/themes/stellar/
├─ layouts/
│  └─ index.blade.php          # @themeAssets in <head>, slot in body
├─ partials/
│  ├─ comment-item.blade.php
│  └─ post-comments.blade.php
├─ home.blade.php
├─ single.blade.php
├─ category.blade.php
├─ categories.blade.php
├─ page.blade.php
├─ author.blade.php
├─ src/
│  ├─ css/app.css              # Tailwind 4 entry
│  ├─ js/app.js                # Alpine + dark-mode + RTL toggle
│  ├─ assets/                  # → dist/assets/
│  └─ fonts/                   # → dist/assets/fonts/
├─ dist/
│  ├─ app.css                  # ← npm run build
│  ├─ app.js
│  ├─ custom.css               # editable in admin
│  └─ custom.js                # editable in admin
├─ images/
├─ theme.json
├─ package.json
├─ tailwind.config.js
├─ copy-assets.cjs             # src/assets + src/fonts → dist/assets
├─ theme-publish.cjs           # invokes `mks:theme-publish` from theme dir
├─ .gitignore
└─ BUILD.md
```

If you confirm the optional override prompt, you also get:

```
├─ theme.php                   # registers page overrides + custom routes
└─ php/
   └─ Livewire/
```

## `theme.json`

```json
{
  "name": "Stellar",
  "version": "1.0.0",
  "author": "Acme",
  "description": "A modern theme for MKSine",
  "screenshot": "screenshot.png",
  "assets": {
    "css": ["dist/app.css", "dist/custom.css"],
    "js":  ["dist/app.js",  "dist/custom.js"]
  }
}
```

`ThemeManager::discover()` reads this file. Themes without a `theme.json` are silently skipped. Invalid JSON logs a `warning` and is skipped.

`assets.css` / `assets.js` is the **ordered list** the `@themeAssets` directive renders into the `<head>`. Paths are relative to the published asset root (`public/themes/{id}/` for project themes, `public/vendor/mksine/themes/{id}/` for package themes). The two `custom.*` entries are special: when the admin Theme Manager has saved custom CSS/JS into `storage/app/theme-custom/`, the directive serves those instead.

## Build and publish

```bash
cd resources/views/themes/stellar
npm install
npm run build           # compiles src/ → dist/ and copies assets, then runs mks:theme-publish
```

Production servers do **not** need Node. Commit `dist/` into git ([ADR 002](../../adr/002-committed-plugin-assets.md)).

If you want to publish from the project root without entering the theme dir:

```bash
php artisan mks:theme-publish stellar
```

This copies `dist/`, `images/`, and the theme `screenshot` into `public/themes/stellar/` (project) or `public/vendor/mksine/themes/stellar/` (package). Publish all themes at once by omitting the identifier:

```bash
php artisan mks:theme-publish
```

## Activate

From the admin: **Appearance → Themes → Activate**. Programmatic equivalent:

```php
app(\Miran\Mksine\Core\Theme\ThemeManager::class)->activate('stellar');
```

`activate()` writes the active record (`mks_themes` table via the `Theme` model), clears the in-memory active-theme cache, and — on success and only when `lang_path()` is available — copies the theme’s translations into `lang/vendor/theme-stellar/`.

## What you don’t get for free

- **No CSS isolation.** Theme CSS lives in the same DOM as Filament’s admin assets when both are loaded. Only the **frontend** loads `@themeAssets`; Filament panels do not, so this is rarely a problem in practice. But if you embed Filament Livewire components in the frontend, expect class collisions if you use `@apply` recklessly.
- **No theme inheritance.** Themes do not inherit from one another. If you want a “base + variant”, ship two themes that share a `php/` namespace and pull from common partials.
- **No automatic asset hashing.** `dist/app.css` is what the `<link>` tag points at — no Vite manifest, no fingerprint. If you change the file, browsers cache it. Use a long-cache-bust strategy at the edge (CDN purge, query string in `extra assets`, or move to a real Vite pipeline if cache is a real problem).

## See also

- [Views and layouts](views-and-layouts.md)
- [Assets and publish](assets-and-publish.md)
- [Translations](translations.md)
- [Custom asset storage](custom-asset-storage.md)
- Reference: [`ThemeManager`](../../reference/facades-and-managers.md#thememanager), [`mks:make-theme` / `mks:theme-publish`](../../reference/commands.md#themes)
