---
title: Plugin translations
description: How to ship plugin language files, publish them to lang/vendor/, and override per locale.
order: 16
---

# Plugin translations

Plugin translations live under `{plugin_root}/{id}/resources/lang/{locale}/{file}.php` (preferred) or `{id}/lang/{locale}/{file}.php`. MKSine looks at both when publishing.

## Where to put strings

```
my-plugin/
└── resources/
    └── lang/
        ├── en/
        │   └── messages.php
        ├── fa/
        │   └── messages.php
        └── ar/
            └── messages.php
```

Translation file format is plain Laravel:

```php
return [
    'product' => [
        'singular' => 'Product',
        'plural'   => 'Products',
    ],
    'created'  => 'Product created.',
];
```

## Reference strings from code

Use Laravel’s `__()` and the `vendor/{plugin-id}::file.key` namespace:

```php
__('my-plugin::messages.product.singular');
```

In Filament:

```php
TextInput::make('name')->label(__('my-plugin::messages.name'));
```

In Blade:

```blade
{{ __('my-plugin::messages.created') }}
```

The `my-plugin::` prefix is the **plugin id** because that’s the namespace MKSine registers when the plugin boots (via the package’s view+lang namespace integration). Keep file names short — `messages.php`, `validation.php` — to avoid `my-plugin::filament_resources_pages_list.title`-style horror.

## Publish to the application’s `lang/`

```bash
php artisan mks-plugin:publish-lang my-plugin
```

What it does ([`PluginManifest::publishTranslations()`](../../../src/Core/Plugins/PluginManifest.php)):

- Copies `resources/lang/` (or `lang/`) to `lang/vendor/{id}/`.
- **Always overwrites** — your plugin’s shipped strings win on every publish.

This is also called automatically during `mks-plugin:activate`. You only need to run `mks-plugin:publish-lang` manually after editing strings without re-activating.

## Overriding per project

If the host application needs to override a string, do **not** edit `lang/vendor/{id}/`. The next publish will wipe it.

Use Laravel’s standard override path: `lang/vendor/{plugin-id}/{locale}/{file}.php` is the publish target, but the lookup order Laravel uses for namespaced translations is:

1. `lang/vendor/{namespace}/{locale}/{file}.php` (publish target — overwritten by the plugin).
2. The plugin’s own `resources/lang/{locale}/{file}.php` (source).

Therefore, the canonical override pattern for namespaced lang files is:

- Publish once, then **commit** the desired overrides outside the publish path. A common solution is to add a custom translation file in the host app (e.g. `lang/{locale}/my-plugin-overrides.php`) and call it explicitly: `__('my-plugin-overrides.product.singular')` — i.e. wrap the plugin’s call.

If you control both sides, prefer adding a "label resolver" in the plugin (e.g. an event/hook the host can intercept) instead of fighting the publish flow.

## Adding a new locale

1. Create the locale folder in your plugin: `resources/lang/it/messages.php`.
2. Re-run publish: `php artisan mks-plugin:publish-lang my-plugin`.
3. Make sure the host app actually has the locale registered in `config/app.php` and `lang/{locale}/` exists for non-namespaced strings.

There is no command to dump missing keys — write a test that asserts each locale has the same set of top-level keys as `en/`. (Yes, you should write this test. Translation drift is a real category of bug.)

## Filament translations the plugin uses

If your plugin reuses Filament’s own labels (`Save`, `Cancel`, …), don’t copy them into your plugin lang. Filament publishes its own translations; rely on those. Override only what is genuinely yours.

## Pitfalls

- **Forgetting to re-publish after editing** — production sees stale strings. Make `mks-plugin:publish-lang` part of the deploy script (or commit `lang/vendor/{id}/` so deploy doesn’t need it).
- **Mixing publishing strategies**: either always-publish or commit-published. Don’t commit `lang/vendor/{id}/` and also re-publish on deploy unless you’re comfortable with the publish step overwriting committed files.
- **Loose key naming** (`my-plugin::title`, `my-plugin::name`, …) collides with future strings. Group under namespaces (`my-plugin::resources.product.title`).

## See also

- [Plugin lifecycle](lifecycle.md) — when activate publishes translations automatically.
- [Theme translations](../themes/translations.md) — same flow, themed at `lang/vendor/theme-{id}/`.
- [Localization workflow](../localization/translations.md) — host-app side.
