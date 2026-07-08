---
title: Views, layouts, and overrides
---

# Views, layouts, and overrides

A theme is rendered through three concentric layers:

1. The package’s **frontend Livewire components** (Home, CategoryList, CategoryShow, PostList, PostShow, PageShow, AuthorShow) — these are the routed pages.
2. The active theme’s **page templates** (`home.blade.php`, `single.blade.php`, …) — the components include these by namespace.
3. The active theme’s **layout** (`layouts/index.blade.php`) — wraps every page and renders `@themeAssets`.

Plugins and themes can override any one of these layers without forking the others.

## View namespacing

`ThemeManager::getViewNamespace()` returns the Blade namespace for the active theme:

| Theme source | Namespace                    |
| ------------ | ---------------------------- |
| Package      | `mksine::themes.{identifier}` |
| Project      | `theme::{identifier}`         |

The package registers the `theme` view hint at boot via `ThemeManager::registerProjectThemeViews()`. Project themes also get a per-theme namespace `theme::{id}`, so partials can reference views inside the same theme as `theme::stellar.partials.foo`.

Helper short-cuts:

```php
theme_view('home')           // → 'mksine::themes.mksine.home' or 'theme::stellar.home'
theme_layout()               // → 'mksine::themes.mksine.layouts.index' (etc.)
$themeManager->view('home');
$themeManager->layout();
```

## Page templates

The package’s frontend Livewire components include `theme_view($key)` to render the active theme’s template. The expected file names per page key:

| Page key       | Template                | Variables (already in scope)        |
| -------------- | ----------------------- | ----------------------------------- |
| `home`         | `home.blade.php`        | `$latestPosts`, `$categories`       |
| `single`       | `single.blade.php`      | `$post`                             |
| `category`     | `category.blade.php`    | `$category`, `$posts` (paginated)   |
| `categories`   | `categories.blade.php`  | `$categories`                       |
| `page`         | `page.blade.php`        | `$page`                             |
| `author`       | `author.blade.php`      | `$author`, `$posts` (paginated)     |

These names are **conventional**, not validated. If you omit `single.blade.php` from your theme, the router silently 500s when someone visits a post. Add a smoke test for each page when you ship a theme.

## Layout

`layouts/index.blade.php` is the wrapper. The scaffold-generated layout looks like:

```blade
<!DOCTYPE html>
<html lang="…" dir="…">
<head>
    @themeAssets
</head>
<body>
    @themeDoAction('layout.body_start')
    {{ $slot }}
</body>
</html>
```

`@themeDoAction('layout.body_start')` is **required** for the [frontend admin bar](../storefront/frontend-admin-bar.md). The package registers the bar renderer on this hook; if your layout omits it, panel users will not see the storefront toolbar.

Page templates render into `$slot`. The `@themeAssets` directive emits the CSS/JS tags from `theme.json`, plus admin-edited `dist/custom.*`, plus extra-asset URLs and runtime-enqueued tags ([Custom asset storage](custom-asset-storage.md)).

## `theme.php` — backend overrides and routes

If you ran `mks:make-theme` with the override prompt enabled, your theme has a `theme.php` file at its root. `ThemeBootstrap::boot()` loads this file at request boot and exposes two registration helpers:

```php
// inside theme.php
$register_override('home', \Themes\Stellar\Livewire\Home::class);

$register_routes(function () {
    \Illuminate\Support\Facades\Route::get('/gallery', \Themes\Stellar\Livewire\Gallery::class)
        ->name('gallery');
});
```

What this does:

- `$register_override($page, $componentClass)` stores the override in `ThemeRegistry`. The package’s router consults this when resolving the page → Livewire component mapping. Valid `$page` values: `home`, `category-list`, `category-show`, `post-list`, `post-show`, `page-show`, `author-show`.
- `$register_routes($callback)` defers the callback until the package registers theme routes. Use this for entirely new URLs your theme adds.

The `php/` directory next to `theme.php` is auto-PSR-4-registered under `Themes\{StudlyIdentifier}\`. So `php/Livewire/Home.php` becomes `Themes\Stellar\Livewire\Home`. The autoload is registered both during `ThemeBootstrap::boot()` and during Livewire’s sub-request boot, so AJAX requests still find your classes.

> **Watch the namespace.** `ThemeBootstrap::registerAutoloadForTheme()` builds the namespace from the identifier by stripping dashes and StudlyCasing it. `stellar` → `Stellar`, `dark-mode` → `DarkMode`, `john_doe` → `John_doe`. Pick an identifier that yields a clean namespace; **renaming the theme later breaks your `php/` autoload silently**.

## Action hooks for templates (`@themeDoAction`)

Themes can expose injection points without forking. Inside a template:

```blade
@themeDoAction('home.before_hero')
```

**Built-in layout hook:** `layout.body_start` — fired at the top of `<body>`. Required for the [frontend admin bar](../storefront/frontend-admin-bar.md). Included in the default theme layout and in `mks:make-theme` scaffolds.

A plugin (or theme) registers a callback once during boot:

```php
theme_add_action('home.before_hero', function () {
    return view('myplugin::widgets.promo-banner')->render();
}, priority: 10);
```

Callbacks may `echo` or `return` an HTML string; both are concatenated. Lower priority runs first. Exceptions are swallowed: the manager logs a comment in HTML when `app.debug` is true (`<!-- Theme hook error […]: … -->`), nothing in production. This is intentional — a misbehaving plugin shouldn’t blank out the homepage — but it also means **silent failures**. Add an integration test that asserts your hook output is present.

## Pagination view

The scaffold uses `mksine::components.pagination` for paginators. If you want a different paginator template, set it inside your theme’s `theme.php`:

```php
\Illuminate\Pagination\Paginator::defaultView('theme::stellar.partials.pagination');
```

## See also

- [Creating a theme](creating-a-theme.md)
- [Assets and publish](assets-and-publish.md)
- [Custom asset storage](custom-asset-storage.md)
- [Frontend admin bar](../storefront/frontend-admin-bar.md)
- Reference: [`ThemeManager`](../../reference/facades-and-managers.md#thememanager), [`ThemeBladeDirectives`](../../reference/facades-and-managers.md#themebladedirectives)
