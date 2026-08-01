---
title: Assets and Vite
description: How plugin CSS/JS is built, published, and loaded — including the Filament collision rules.
order: 15
---

# Assets and Vite

Plugins ship frontend assets from `resources/css/` and `resources/js/`, build them with Vite into `resources/dist/`, and publish them into `public/plugins/{id}/`. The package never runs `npm` for you — every build is explicit.

## Source layout

The scaffolded layout (from `mks-plugin:make`):

```
resources/
├── css/app.css        ← Tailwind entry
├── js/app.js          ← JS entry (imports CSS)
└── dist/              ← Vite output (built artifacts)
    ├── app.css
    ├── app.js
    └── .gitkeep
```

`resources/dist/` is **part of the plugin**. Its contents are gitignored by convention but the directory itself is tracked via `.gitkeep`. See the commit-policy section below.

## Vite configuration

The default `vite.config.js` writes to `resources/dist/` and emits stable filenames (no hashing):

```js
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: false,
        rollupOptions: {
            input: 'resources/js/app.js',
            output: {
                assetFileNames: '[name].[ext]',
                entryFileNames: '[name].js',
            },
        },
    },
});
```

`emptyOutDir: false` is intentional — `resources/dist/` may contain other files (e.g. fonts, images you committed) that Vite should not wipe.

If you change the output filenames, update the panel asset registration accordingly. The default loader looks for `app.css` / `app.js`.

## NPM scripts

The default `package.json` includes:

```json
{
  "scripts": {
    "dev":   "vite",
    "build": "vite build && npm run publish && npm run sync:mksine-tailwind && npm run sync:filament-assets",
    "publish": "cd ../.. && php artisan mks-plugin:publish {plugin-id}",
    "sync:mksine-tailwind": "node ../../vendor/miran/mksine/bin/build-styles.js",
    "sync:filament-assets": "node ../../vendor/miran/mksine/bin/filament-assets.js"
  }
}
```

What each chained step does:

- `vite build` → produces `resources/dist/{app.css,app.js}`.
- `mks-plugin:publish {id}` → copies `resources/dist/` to `public/plugins/{id}/`.
- `sync:mksine-tailwind` → rebuilds the **package** Tailwind (`vendor/miran/mksine`), so Filament classes the plugin uses get included in the panel CSS bundle.
- `sync:filament-assets` → keeps Filament’s vendor assets in `public/vendor/filament` aligned with the version pinned in `composer.json`.

On a Composer install there is no `packages/mksine` tree — scripts resolve the package via `vendor/miran/mksine` (monorepos symlink there too). The Composer archive ships pre-built `resources/dist/mksine.css`; `sync:mksine-tailwind` skips rebuilding when `package.json` is absent unless you run `npm install` inside `vendor/miran/mksine` for a full Tailwind rebuild.

Skip any step you don’t need (`npm run build && cd ../.. && php artisan mks-plugin:publish my-plugin` is the minimum). The chain only matters in a monorepo development setup.

## Tailwind: scope-or-die

Plugin CSS is loaded into the admin panel and (depending on your panel setup) the storefront. Filament ships its own Tailwind preflight; if you ship a second one with conflicting resets, **the panel UI will break in subtle ways** — wrong button heights, lost borders, cut-off labels.

Rules to follow:

1. Use `@import 'tailwindcss' source(none);` and add explicit `@source` directives. The scaffolded `resources/css/app.css` does this:

   ```css
   @import 'tailwindcss' source(none);
   @source '../../src';
   @source '../views';
   ```

2. Scope your styles. Either:
   - Wrap plugin styles inside an explicit class (`.my-plugin { ... }`) and apply that class to your roots; **or**
   - Use Tailwind utilities only on elements you render — never restyle Filament’s `<button>`, `<input>`, etc. globally.

3. Do not include `@tailwind base;` in plugin CSS unless you have isolated it (e.g. shadow DOM). Preflight collides with Filament’s.

If you violate these, expect to spend an afternoon debugging "why does the table row height change after I activate the plugin?".

## How assets are loaded

When the plugin is active and the published file exists, the panel adds `<link rel="stylesheet" href="/plugins/{id}/app.css">` and `<script src="/plugins/{id}/app.js" defer>` to the admin layout. Both URLs are produced by `PluginManifest::publishedCssUrl()` and `publishedJsUrl()`, which return `null` if the file is missing — no broken `<link>` tags.

For storefront usage, query the manifest yourself:

```php
$manifest = app(\Miran\Mksine\Core\Plugins\PluginManager::class)->getManifest('my-plugin');
$css = $manifest?->publishedCssUrl();
```

…or just use `asset('plugins/my-plugin/app.css')` and accept a 404 when not published.

## Publishing

```bash
php artisan mks-plugin:publish my-plugin --force
```

This copies `resources/dist/` to `public/plugins/{id}/`. `--force` overwrites without asking. Without an argument, every active plugin is published — handy in deploy scripts.

The reverse, `PluginManifest::removePublishedAssets()`, can be called from `uninstall(true)` to clean up `public/plugins/{id}/` when fully removing the plugin.

## Commit policy

Two strategies:

| Strategy | Commit | Don’t commit | Trade-off |
|----------|--------|--------------|-----------|
| **Built artifacts in git** (default) | `resources/dist/`, `public/plugins/{id}/` | `node_modules` | Production deploys never need `npm`. Larger PRs. Keeps deploy boxes light. |
| **Build at deploy time** | source only | `dist/`, `public/plugins/{id}/` | Smaller diffs, but every deploy box needs a Node toolchain plus `mks-plugin:publish` in the pipeline. |

MKSine assumes the first strategy by default — see [ADR 002: committed plugin assets](../../adr/002-committed-plugin-assets.md). The `mks:release-archive` allowlist for `public/` includes `plugins/` precisely so committed assets travel with the archive.

## Pitfalls

- **Editing `resources/dist/` by hand**: Vite will overwrite it on the next build. Treat `dist/` as generated.
- **Plugin assets path served by storage symlink**: don’t. Use the dedicated `public/plugins/{id}/` directory.
- **Cache headers on the panel**: Filament’s asset URLs are usually cache-busted by Vite hashes. Plugin assets are **not** hashed (filenames are stable). Bust the cache by appending `?v={plugin_version}` if your CDN keeps stale copies — or set proper `Cache-Control` headers.
- **Vendor splitting**: your plugin bundle should not include React/Vue copies if the host already loads them. Externalise via Vite `build.rollupOptions.external` and document the host-side requirement.

## See also

- [Composer and publishes presets](composer-and-publishes.md) — when third-party assets need vendoring instead of npm-installing.
- [Operations: deployment & hosting](../../operations/deployment-hosting.md) — how `public/` is treated during release archives.
- [Theme assets](../themes/assets-and-publish.md) — same idea, different target directories.
