---
title: Shortcodes
description: WordPress-style shortcodes in rich text — admin insert UI, plugin registration, Livewire widgets, and render cache.
---

# Shortcodes

MKSine processes **WordPress-style shortcodes** in rich HTML content on the storefront: posts, classic pages, category descriptions, and page-builder text fields (Text, Tabs, Accordion).

Syntax:

```
[year]
[site_name /]
[my_tag foo="bar"]Inner HTML[/my_tag]
```

Shortcodes are **parsed at render time**. The database stores the raw markup (same as WordPress).

## Using shortcodes in the admin (CKEditor)

Every CKEditor field in Filament (posts, pages, categories, etc.) has an **Insert shortcode** toolbar button (bracket icon, next to **Insert media**).

1. Place the cursor where you want the shortcode.
2. Click **Insert shortcode**.
3. Pick a tag from the catalog (built-in and plugin-registered shortcodes appear here).
4. The example snippet (e.g. `[year]`) is inserted into the editor.

The catalog is built from `ShortcodeRegistry::adminCatalog()`. Plugins can supply labels, descriptions, and example snippets so editors know what each tag does.

> **Storefront only:** Shortcodes are **executed** when the theme calls `mks_render_content()`. The admin editor shows the raw `[tag]` markup.

## Feature flag

`config('mksine.features.shortcodes')` — env: `MKS_CMS_SHORTCODES`, default `true`.

When disabled, `mks_render_content()` returns HTML unchanged.

Parser limits: `config('mksine.shortcodes.max_depth')` (default `5`), `config('mksine.shortcodes.max_passes')` (default `2`).

Render cache (see below): `config('mksine.shortcodes.cache.*')`.

See [Configuration → features](../../reference/configuration.md#features).

## Rendering content

Always use the helper in Blade instead of raw `{!! $model->content !!}`:

```blade
{!! mks_render_content($post->content) !!}
```

For category descriptions (HTML from CKEditor):

```blade
@if (filled($category->description))
    <div class="prose prose-sm max-w-none dark:prose-invert">
        {!! mks_render_content($category->description) !!}
    </div>
@endif
```

`mks_render_content()` resolves context in this order:

1. Explicit second argument
2. `View::shared('mksShortcodeContext')` (set by frontend Livewire components)
3. Empty context

Frontend components share context automatically:

- `PostShow` → post
- `PageShow` → page
- `CategoryShow` → category

## Built-in shortcodes

| Tag | Output |
| --- | ------ |
| `[year]` | Current year (Shamsi when `date_calendar` setting is `shamsi`) |
| `[site_name]` | `mks_setting('site_name')` or `config('app.name')`, HTML-escaped |

## Registering a new shortcode (plugin)

In your plugin’s `boot()` method:

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\Shortcodes\ShortcodeCatalogEntry;
use Miran\Mksine\Core\Shortcodes\ShortcodeContext;
use Miran\Mksine\Core\Shortcodes\ShortcodeHandlerInterface;

Hooks::addShortcode(
    tag: 'ecom_products',
    handler: function (array $attrs, ?string $content, ShortcodeContext $context): string {
        $ids = array_filter(array_map('intval', explode(',', $attrs['ids'] ?? '')));

        return view('ecom::shortcodes.product-grid', ['ids' => $ids])->render();
    },
    priority: 10,
    catalog: new ShortcodeCatalogEntry(
        tag: 'ecom_products',
        label: 'Product grid',
        description: 'Renders a grid of products by ID.',
        example: '[ecom_products ids="1,2,3"]',
        selfClosing: true,
    ),
);
```

Or implement `ShortcodeHandlerInterface` and pass the class name:

```php
Hooks::addShortcode(
    tag: 'gallery',
    handler: GalleryShortcode::class,
    catalog: new ShortcodeCatalogEntry(
        tag: 'gallery',
        label: 'Gallery',
        example: '[gallery ids="10,11"]',
    ),
);
```

### Admin catalog entry (`ShortcodeCatalogEntry`)

| Property | Purpose |
| -------- | ------- |
| `tag` | Shortcode name (lowercase, `[a-z0-9_]`) |
| `label` | Title shown in the CKEditor picker |
| `description` | Optional help text in the picker |
| `example` | Snippet inserted when the editor picks this tag (defaults to `[tag]`) |
| `selfClosing` | Metadata only; documents whether the tag is typically self-closing |

If you omit `catalog`, a minimal entry is created automatically (`label` = tag name, `example` = `[tag]`).

Filter the catalog before it reaches the admin UI:

```php
use Miran\Mksine\Core\Shortcodes\ShortcodeRegistry;

Hooks::addFilter(ShortcodeRegistry::ADMIN_CATALOG_FILTER, function (array $entries): array {
    // Remove or reorder entries for specific panels
    return $entries;
});
```

Translation keys for built-in catalog labels live in `mksine::shortcodes.catalog.*` (see `resources/lang/*/shortcodes.php`).

### Handler signature

```php
/**
 * @param  array<string, string>  $attrs
 */
public function handle(array $attrs, ?string $content, ShortcodeContext $context): string;
```

- **Self-closing:** `[tag /]` or `[tag]` (when no `[/tag]` follows)
- **Enclosing:** `[tag]content[/tag]` — inner content is processed for nested shortcodes before the handler runs
- **Unknown tags** are left unchanged in the HTML output

### Context

`ShortcodeContext` exposes optional `page`, `post`, and `category` models for contextual output (e.g. “related products on this category page”).

Build manually:

```php
mks_shortcode_context(page: $page, post: $post, category: $category);
```

## Interactive shortcodes (Livewire)

For storefront widgets that need Livewire (cart mini-widget, dynamic forms, etc.), use `Hooks::addLivewireShortcode()`:

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Core\Shortcodes\ShortcodeCatalogEntry;

Hooks::addLivewireShortcode(
    tag: 'cart_widget',
    componentClass: \Ecom\Livewire\CartWidget::class,
    defaultParams: ['compact' => true],
    priority: 10,
    catalog: new ShortcodeCatalogEntry(
        tag: 'cart_widget',
        label: 'Cart widget',
        description: 'Interactive cart summary (Livewire).',
        example: '[cart_widget compact="true"]',
    ),
);
```

- Attributes on the tag are merged into the component mount params (`[cart_widget foo="bar"]` → `['compact' => true, 'foo' => 'bar']`).
- Livewire shortcodes are **never cached** by `ContentRenderer` (each request mounts a fresh component).
- The component class must be a standard Livewire full-page or embedded component.

Under the hood this calls `ShortcodeLivewire::mount($componentClass, $params)`.

## Render cache

Heavy pages with many shortcodes can enable render caching. When enabled, `mks_render_content()` caches the fully parsed HTML.

| Key | Env | Default | Notes |
| --- | --- | ------- | ----- |
| `shortcodes.cache.enabled` | `MKS_CMS_SHORTCODES_CACHE` | `true` | Master switch |
| `shortcodes.cache.ttl` | `MKS_CMS_SHORTCODES_CACHE_TTL` | `3600` | TTL in seconds |
| `shortcodes.cache.store` | `MKS_CMS_SHORTCODES_CACHE_STORE` | `null` | Laravel cache store name; `null` = default driver |

Cache key includes: content hash, registry version (bumped when shortcodes are registered), locale, calendar, and related model IDs/timestamps from `ShortcodeContext`.

**Skipped when:**

- Feature disabled or empty content
- Running unit tests
- Content contains a registered Livewire shortcode tag
- Cache disabled in config

Registering or changing shortcode handlers bumps `ShortcodeRegistry::registryVersion()`, invalidating cached output automatically.

## Runtime filters

| Filter | When |
| ------ | ---- |
| `mksine.content.before_shortcodes` | Before parsing; `(string $html, ShortcodeContext $ctx)` |
| `mksine.content.after_shortcodes` | After parsing; same signature |
| `mksine.shortcode.{tag}` | Override a single tag’s rendered output |
| `mksine.shortcodes.admin_catalog` | Before CKEditor picker; `(list<ShortcodeCatalogEntry> $entries)` |

Constants on `ContentRenderer`: `FILTER_BEFORE`, `FILTER_AFTER`.  
Catalog filter constant: `ShortcodeRegistry::ADMIN_CATALOG_FILTER`.

Per-tag filter example:

```php
Hooks::addFilter('mksine.shortcode.year', fn (string $html) => '۱۴۰۴');
```

## Stripping shortcodes

For excerpts and plain-text snippets:

```php
$plain = mks_strip_shortcodes($post->content);
```

Does not execute handlers — removes markup only.

## Theme requirement

Templates must call `mks_render_content()` (included in the default MKSine theme, `mks:make-theme` scaffolds, and Voltech). Custom themes that still use `{{ $post->content }}` or `{!! $post->content !!}` without the helper will not process shortcodes.

Fields that should support shortcodes:

- Post / page body content
- Category description
- Page-builder Text, Tabs, and Accordion components

## Security notes

- Handler return values are injected as HTML. Escape user data inside handlers/views.
- Attributes are decoded but not executed. Do not pass raw attributes into `eval()` or shell commands.
- Nested depth and pass limits prevent runaway recursion.
- Livewire shortcodes mount real components — authorize sensitive data inside the component, not in the shortcode tag.

## See also

- [Runtime registration](../hooks/runtime-registration.md) — `Hooks::addShortcode`, `Hooks::addLivewireShortcode`
- [Configuration](../../reference/configuration.md)
- [API stability → shortcodes](../../reference/stability.md)
