---
title: Custom asset storage
---

# Custom asset storage

Themes ship a static `dist/` directory baked into the package or project repo. But operators often need to tweak CSS or JS **without redeploying** — a colour change, a tracking pixel, an ad-hoc fix. MKSine supports this through a storage-backed override layer.

This page documents how that layer works, what URLs it serves, and where the data lives.

## What "custom" means

There are three concepts that get conflated; treat them as distinct:

| Concept                | Source                                              | Edited where               | Persisted in                                |
| ---------------------- | --------------------------------------------------- | -------------------------- | ------------------------------------------- |
| **Theme dist asset**   | `theme.json` `assets.css` / `assets.js` entries     | Theme source (`src/`), then `npm run build` | `{theme}/dist/`, copied to public on publish |
| **Custom override**    | A specific `dist/custom.css` / `dist/custom.js`     | Admin UI (Theme Manager → Custom CSS/JS) | `storage/app/theme-custom/{id}.css` / `.js` |
| **Extra assets**       | Arbitrary URLs or paths added from the admin UI     | Admin UI (Theme Manager)   | `storage/app/theme-custom/{id}-extra-assets.json` |

Custom override and extra assets are **operator-visible**, **server-side**, and **per-installation**. They are not part of the theme repo and they survive theme upgrades.

## How the override is wired

`theme.json` declares two well-known asset paths the override layer hooks into:

```json
{
  "assets": {
    "css": ["dist/app.css", "dist/custom.css"],
    "js":  ["dist/app.js",  "dist/custom.js"]
  }
}
```

When the directive renders these paths, `ThemeBladeDirectives::resolveAssetUrl()` checks for an admin-saved override:

```php
if ($path === 'dist/custom.css' && $manager->hasCustomAsset($theme->identifier, 'css')) {
    return route('mksine.theme.custom.asset', ['identifier' => …, 'type' => 'css']);
}
```

If the override exists, the URL points at a **streamed Laravel route**, not at the static file in `public/`. Otherwise, it falls back to the static path under `public/themes/{id}/dist/custom.css` (which is typically empty / a placeholder).

> The two override slots are hardcoded to **`dist/custom.css`** and **`dist/custom.js`**. Other asset names cannot be overridden through this mechanism — they always resolve to the static file. If you want a third override slot, you need to fork the directive.

## Where the data lives

Everything is under `storage_path('app/theme-custom/')`:

```
storage/app/theme-custom/
├─ stellar.css                       # custom CSS for theme "stellar"
├─ stellar.js                        # custom JS for theme "stellar"
├─ stellar-extra-assets.json         # extra CSS/JS URLs for theme "stellar"
└─ another-theme.css
```

Implications you need to own:

- **Storage permissions.** The web user must be able to read and write this directory. The Filament admin writes; the streamed route reads. If the directory is missing, `putCustomContent()` calls `File::ensureDirectoryExists()` first, but only when an admin saves something — until that happens, the directory does not exist.
- **Backups must include `storage/app/`.** Otherwise an operator-visible feature will silently disappear on restore.
- **Multi-server installations need shared storage.** This data is on local disk by default. If you run multiple web nodes, point `storage/app/theme-custom/` at a shared volume (NFS, EFS) or migrate the override layer to a `Storage::disk('s3')` driver. There is no out-of-the-box S3 backend; that is a fork.

## The streaming route

The route name is `mksine.theme.custom.asset`. It accepts `{identifier}` and `{type}` (`css` or `js`) and returns the file body with the correct `Content-Type`. There is **no caching layer**, **no ETag**, and **no fingerprint**. Each request reads the file from disk.

For low-traffic admin sites this is fine. For high-traffic frontends, put a CDN or reverse-proxy cache in front of the route and bust it on save. The admin save handler is the right place to call `Cache::forget` / CDN purge if you add one.

## The screenshot route

Themes can ship a `screenshot.png` next to `theme.json`. `ThemeManager::getScreenshotUrl()` returns a `route('mksine.theme.screenshot', …)` URL that streams the file from the theme directory. This is used by the admin Theme picker to preview themes _without_ requiring publishing first.

The route exists primarily so the admin UI works on a fresh install before anyone has run `mks:theme-publish`. In the published state, the screenshot is also copied to `public/themes/{id}/screenshot.png`, but the route is what the picker uses.

## Extra assets

`storage/app/theme-custom/{id}-extra-assets.json` is a flat JSON object with two arrays:

```json
{
  "css": ["https://fonts.example/typeface.css", "dist/extra/banner.css"],
  "js":  ["dist/extra/banner.js"]
}
```

Each entry is either a full URL (passed through verbatim) or a path that resolves the same way as `theme.json` assets. Extra assets are emitted right after the canonical `theme.json` assets and before runtime enqueues.

## Honest limitations

- **No version control on the override.** Whatever the admin saves overwrites the previous content. There is no audit trail and no diff. If you need that, write your own observer that copies the file into a versioned location on save.
- **No syntax validation.** A typo in the CSS or a SyntaxError in the JS lands on every page until someone notices. Pair the override save with a CSS lint or an eval-in-sandbox check if that matters in your environment.
- **No multi-locale split.** One CSS, one JS per theme, full stop. If you need different styles per locale, branch inside the CSS itself with `:lang(fa)` selectors or the `<html dir>` selector.
- **The override is keyed by theme identifier.** Renaming a theme abandons the override; create a copy in storage if you need to migrate.

## See also

- [Assets and publish](assets-and-publish.md)
- [Creating a theme](creating-a-theme.md)
- Reference: [`ThemeManager`](../../reference/facades-and-managers.md#thememanager)
