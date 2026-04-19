---
title: Theme assets and publishing
---

# Theme assets and publishing

Themes ship a **committed `dist/`** directory of compiled CSS/JS. The publishing pipeline copies that directory into a public location, and the `@themeAssets` Blade directive renders the right `<link>` and `<script>` tags pointing at it.

This page describes the entire round trip and the three ways assets can end up on the page.

## The three sources of theme assets

`@themeAssets` (defined in `ThemeBladeDirectives::renderThemeAssets()`) emits tags from these sources, in this order:

1. **`theme.json` `assets.css` and `assets.js`** — the canonical, file-on-disk assets the theme always ships.
2. **Extra assets** stored in `storage/app/theme-custom/{identifier}-extra-assets.json` — added from the admin Theme Manager without editing Blade. Useful for one-off CDN scripts or path-based overrides.
3. **Runtime-enqueued assets** added via `theme_enqueue_style()` / `theme_enqueue_script()` (request-scoped). Useful for plugins that conditionally load an asset on certain pages.

All three resolve through `resolveAssetUrl()`, so paths are handled identically:

- `http://`, `https://`, or `//` prefixes pass through as-is.
- `dist/custom.css` and `dist/custom.js` route to the **storage-backed** download URL (`mksine.theme.custom.asset`) when an admin override exists.
- Otherwise the path resolves to:
  - `asset("themes/{id}/{path}")` for project themes,
  - `asset("vendor/mksine/themes/{id}/{path}")` for package themes.

## The build pipeline (per scaffolded theme)

`mks:make-theme` writes a `package.json` configured for Tailwind 4 + esbuild. The relevant scripts:

```jsonc
{
  "scripts": {
    "dev:css":   "npx @tailwindcss/cli -i src/css/app.css -o dist/app.css --watch",
    "dev:js":    "npx esbuild src/js/app.js --bundle --outfile=dist/app.js --watch",
    "build:css": "npx @tailwindcss/cli -i src/css/app.css -o dist/app.css --minify",
    "build:js":  "npx esbuild src/js/app.js --bundle --minify --outfile=dist/app.js",
    "copy:assets": "node copy-assets.cjs",
    "build": "npm run build:css && npm run build:js && npm run copy:assets && php ../../../../artisan mks:theme-publish {id} && node ../../../../packages/mksine/bin/filament-assets.js",
    "publish": "node theme-publish.cjs"
  }
}
```

In plain terms:

- **`npm run dev`** watches CSS and JS, but **does not** publish to `public/`. It assumes you'll re-run `npm run build` when you want the dev output to land in the public path.
- **`npm run build`** does CSS → `dist/app.css`, JS → `dist/app.js`, then `copy-assets.cjs` (which copies `src/assets` and `src/fonts` into `dist/assets`), then runs `php artisan mks:theme-publish {id} --force`, then re-publishes Filament’s admin assets via `packages/mksine/bin/filament-assets.js`.

> **The hard-coded `../../../../artisan` path assumes** the theme lives at `resources/views/themes/{id}/`. If you keep the theme there (the default), it works. If you move it elsewhere, **the publish step in the npm scripts will silently target the wrong directory**. Either keep the default location or rewrite the scripts.

## What gets published

`ThemeManager::publishAssets($identifier)` copies these from the theme directory to its public destination:

- `dist/` → `…/dist/`
- `images/` (if present) → `…/images/`
- the file referenced by `theme.json` `screenshot` key (if present)

Anything else in the theme directory is **not** copied. If you want extra static files in public, put them under `dist/` or `images/`.

## `theme_enqueue_style` / `theme_enqueue_script`

For request-scoped asset injection (e.g. only enqueue a script when a Page Builder block is present), use the enqueue helpers from a request handler, Livewire component `mount()`, or a `theme_add_action()` callback that runs before the layout renders:

```php
use function Miran\Mksine\Support\theme_enqueue_style;
use function Miran\Mksine\Support\theme_enqueue_script;

theme_enqueue_style('https://cdn.example.com/lightbox.css', ['media' => 'screen']);
theme_enqueue_script('dist/lightbox.js', ['defer' => 'defer']);
```

The `ThemeEnqueue` instance is **request-scoped**: anything you enqueue is rendered only by `@themeAssets` on the current response. Subsequent requests start with an empty queue.

If neither `defer` nor `async` is provided in the script attributes, the directive injects `defer="defer"` automatically (this is intentional — synchronous third-party scripts in the head are almost always wrong).

## Extra assets via the admin Theme Manager

The Theme Manager admin page can add extra CSS/JS URLs (full URLs or relative paths) without editing any Blade file. They are persisted to `storage/app/theme-custom/{id}-extra-assets.json`:

```json
{
  "css": ["https://fonts.example/typeface.css"],
  "js":  ["dist/extra/banner.js"]
}
```

These are emitted right after the `theme.json` assets and before runtime enqueues. They survive across requests and across deployments (storage is not touched by `mks:theme-publish`), but they are **per-installation state** — you cannot ship them with a theme.

## Custom CSS/JS overrides

`dist/custom.css` and `dist/custom.js` are special: when an admin saves content for them in the Theme Manager, the directive serves the storage version (`storage/app/theme-custom/{id}.css`) via a streamed route, not the file in `dist/`. This lets operators tweak styles without redeploying the theme.

See [Custom asset storage](custom-asset-storage.md) for the full storage model and route.

## Cache-busting

There is **no** cache-busting layer. URLs look like `…/dist/app.css` with no query string and no fingerprint. Strategies:

- Append a manual version query string in `theme.json` (e.g. `"dist/app.css?v=4"`) and bump it on each release. The directive does not strip query strings.
- Add a CDN cache purge to your deploy pipeline.
- Replace the build step with Vite + a manifest reader (this is a real fork; not provided out of the box).

For most installations, the manual `?v=` approach is enough. Document it loudly so the next maintainer doesn’t forget to bump it.

## See also

- [Creating a theme](creating-a-theme.md)
- [Views and layouts](views-and-layouts.md)
- [Custom asset storage](custom-asset-storage.md)
- Reference: [`ThemeManager`](../../reference/facades-and-managers.md#thememanager), [`mks:theme-publish`](../../reference/commands.md#themes)
- ADR: [Committed plugin assets](../../adr/002-committed-plugin-assets.md) (same rationale applies to themes)
