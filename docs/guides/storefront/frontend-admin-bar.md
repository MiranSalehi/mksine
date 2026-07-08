---
title: Frontend admin bar
description: WordPress-style storefront toolbar for panel users, hook-based menu items, and admin "View site" link.
---

# Frontend admin bar

MKSine ships a **WordPress-style admin bar** on the public storefront for authenticated users who can access the Filament panel. It also adds a **View site** link inside the admin panel so operators can jump back to the storefront in one click.

These are two separate surfaces:

| Surface | Where | Theme required? | Extension hook |
| ------- | ----- | --------------- | -------------- |
| Storefront admin bar | Top of public pages | Yes — layout must fire `layout.body_start` | `Hooks::filter('frontend_admin_bar.items', …)` |
| Admin "View site" | Filament topbar + user menu | No | Not hookable (always points at the active storefront URL) |

## Feature flag

Controlled by `config('mksine.features.frontend_admin_bar')` (env: `MKS_CMS_FRONTEND_ADMIN_BAR`, default `true`).

When `false`, the storefront bar is not rendered. The admin **View site** link is independent and always registered by `MksinePlugin`.

See [Configuration → features](../../reference/configuration.md#features).

## Storefront bar — who sees it

The bar renders when **all** of the following are true:

1. `frontend_admin_bar` feature is enabled.
2. The visitor is authenticated.
3. The user implements `Filament\Models\Contracts\FilamentUser` and `canAccessPanel()` for the admin panel.
4. The current request is **not** under the Filament panel path (e.g. `/admin`).

Guests and users without panel access never see the bar.

## Theme requirement: `layout.body_start`

The package registers the bar renderer on the theme action hook `layout.body_start`:

```php
// MksineServiceProvider — do not duplicate in themes
theme_add_action('layout.body_start', fn () => app(FrontendAdminBar::class)->render(), priority: 1);
```

Your theme layout must **fire** that hook immediately after `<body>`:

```blade
<body>
    @themeDoAction('layout.body_start')

    {{-- header, main content, … --}}
</body>
```

- The default package theme (`mksine`) and scaffolds from `mks:make-theme` include this line.
- Custom themes (e.g. Voltech) must add it manually to `layouts/index.blade.php` and any other full-page layouts (profile shells, etc.) where the bar should appear.

Without `@themeDoAction('layout.body_start')`, the hook never runs and the bar is silently absent — no error is thrown.

## Menu items — hook contract

Menu items are collected through a **runtime filter**, not hard-coded in the theme.

**Hook name:** `frontend_admin_bar.items` (constant: `FrontendAdminBar::HOOK_ITEMS`)

**Signature:**

```php
/**
 * @param  list<FrontendAdminBarItem>  $items
 * @return list<FrontendAdminBarItem>
 */
function (array $items, FrontendAdminBarContext $context, Filament\Panel $panel): array
```

Register from a plugin `boot()` method (or any service provider that boots on every web request):

```php
use Filament\Panel;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Support\Frontend\FrontendAdminBar;
use Miran\Mksine\Support\Frontend\FrontendAdminBarContext;
use Miran\Mksine\Support\Frontend\FrontendAdminBarItem;

Hooks::addFilter(FrontendAdminBar::HOOK_ITEMS, function (
    array $items,
    FrontendAdminBarContext $context,
    Panel $panel,
): array {
    // Simple link
    $items[] = new FrontendAdminBarItem(
        id: 'myplugin.reports',
        label: 'Reports',
        url: '/admin/reports',
        priority: 40,
    );

    // Dropdown (parent has children, no url required)
    $items[] = new FrontendAdminBarItem(
        id: 'myplugin.tools',
        label: 'Tools',
        priority: 50,
        children: [
            new FrontendAdminBarItem(
                label: 'Import',
                url: '/admin/import',
            ),
            new FrontendAdminBarItem(
                label: 'External docs',
                url: 'https://example.com/docs',
                openInNewTab: true,
            ),
        ],
    );

    return $items;
}, priority: 20);
```

### `FrontendAdminBarItem` fields

| Field | Type | Notes |
| ----- | ---- | ----- |
| `label` | `string` | Visible text (translate in the callback). |
| `url` | `?string` | Required for leaf links. Omit when using `children`. |
| `openInNewTab` | `bool` | Adds `target="_blank"` + `rel="noopener noreferrer"`. |
| `id` | `string` | Optional stable identifier for your plugin (not used for rendering). |
| `priority` | `int` | Lower runs first in the bar (default `10`). |
| `children` | `list<FrontendAdminBarItem>` | When non-empty, renders as a hover/focus dropdown. Empty child lists are dropped. |

Items without a URL and without valid children are skipped during normalization.

### Core CMS items

The package registers its own items (Dashboard, contextual Edit page/post/category, list shortcuts) through `RegisterFrontendAdminBarCoreItems` on the same filter at priority `10`. Plugins should use higher priorities (e.g. `20+`) unless they intentionally need to run before core items.

### Context object

`FrontendAdminBarContext` exposes the current route and resolved models:

| Property | Type | When set |
| -------- | ---- | -------- |
| `routeName` | `string` | Always (Laravel route name). |
| `page` | `?Page` | Home (when mapped to a CMS page), `pages.show`, etc. |
| `post` | `?Post` | `posts.show` |
| `category` | `?Category` | `categories.show` |

Use `$context` to show contextual edit links only on relevant pages.

### Theme action after the bar

Blade fires `@themeDoAction('frontend_admin_bar.after')` at the end of the bar partial. Use this for extra HTML (badges, scripts) that should not go through the items filter:

```php
theme_add_action('frontend_admin_bar.after', function (): string {
    return '<!-- my plugin admin-bar chrome -->';
});
```

## Admin panel — View site

`MksinePlugin` registers:

1. A **topbar** link showing the site name (`StorefrontUrl::siteLabel()` — from `mks_setting('site_name')` or `config('app.name')`).
2. A **user menu** item labelled "View site" / "مشاهده سایت".

Both open the storefront URL in a new tab:

- `route('ecom.shop')` when that route exists.
- Otherwise `route('home')`.
- Fallback: `url('/')`.

No theme changes are required for this behaviour.

## Sticky headers

When the bar is visible, the partial adds `mksine-has-admin-bar` on `<html>` and applies `padding-top` to `body`. Selectors for `.site-header-bar` and `header.sticky` are included for the default theme. Custom themes with fixed headers may need additional CSS keyed off `html.mksine-has-admin-bar`.

## Troubleshooting

| Symptom | Likely cause |
| ------- | ------------ |
| Bar never appears | Theme layout missing `@themeDoAction('layout.body_start')`, or user cannot access the panel, or feature flag off. |
| Bar on admin panel | Should not happen — report as bug if it does. |
| Plugin item missing | Filter not registered in `boot()`, wrong return type, or item has no URL and no children. |
| Dropdown empty | All children failed normalization (missing URLs). |

## See also

- [Views and layouts](../themes/views-and-layouts.md) — `@themeDoAction` and layout hooks.
- [Runtime registration](../hooks/runtime-registration.md) — `Hooks::addFilter` / `Hooks::filter`.
- [Configuration → features](../../reference/configuration.md#features).
