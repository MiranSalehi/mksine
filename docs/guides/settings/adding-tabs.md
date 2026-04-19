---
title: Adding settings tabs
---

# Adding settings tabs

The package’s **Settings** page (Filament page at `Miran\Mksine\Filament\Pages\Settings`) is built from two sources:

1. **Core tabs** — `general`, `permalinks`. Hardcoded in the page class.
2. **Extension tabs** — supplied by `SettingsTabManager`.

Plugins add their own tabs through `SettingsTabManager` without ever touching the page class.

## How storage works

Every field on every tab is persisted to the `settings` table via `Setting::updateOrCreate(['key' => $field], ['value' => $value])`. The page picks **the field name as the key**. Reading is done through `mks_setting('site_name')` (a global helper that hits the `settings` table with a per-request cache).

Two consequences you must internalise:

- **Field names are global.** If you name a field `site_name`, it will overwrite the core `site_name` setting. Namespace your keys (`acme_seo_meta_description`, not `meta_description`).
- **Arrays are JSON-encoded.** The page detects array values and `json_encode()`s them on save; on read, `getSetting()` decodes valid JSON. If you store an array, expect to read an array. Mismatches happen when a setting was first stored as a plain string and later changed to a Repeater — clean up the row manually.

There is **no draft, no validation hooks beyond Filament’s field rules, and no audit log**. A misconfigured tab schema overwrites settings on save.

## Registering a tab

Anywhere in `boot()` of a service provider or a plugin:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\Hooks\SettingsTabManager;

app(SettingsTabManager::class)->registerTab(
    id: 'acme_seo',
    label: fn () => __('acme-seo::settings.tab_label'),
    schema: [
        TextInput::make('acme_seo_meta_description')
            ->label(__('acme-seo::settings.meta_description'))
            ->maxLength(320),

        Toggle::make('acme_seo_enable_og_tags')
            ->label(__('acme-seo::settings.enable_og_tags'))
            ->default(true),
    ],
    sortOrder: 50,
);
```

### Method signature

```php
public function registerTab(
    string $id,
    string|Closure $label,
    array|callable $schema,
    int $sortOrder = 0,
): void;
```

| Argument    | Notes                                                                                                            |
| ----------- | ---------------------------------------------------------------------------------------------------------------- |
| `id`        | Unique tab key. Used as the Filament `Tab::make()` name. Collisions with another plugin’s id silently overlap.   |
| `label`     | Plain string OR a closure for lazy evaluation. **Use a closure** if calling `__()` so the locale is correct per request. |
| `schema`    | Array of Filament form components OR a callable returning that array. Use a callable for lazy/conditional schemas. |
| `sortOrder` | Lower wins. Core tabs have implicit order 0; pick `≥1` to place after them, or use negative numbers to push above. |

### Lazy schema for permission-gated tabs

```php
app(SettingsTabManager::class)->registerTab(
    'acme_billing',
    fn () => __('acme-billing::settings.billing'),
    fn () => auth()->user()?->can('manage-billing')
        ? [TextInput::make('acme_billing_provider')]
        : [],
    sortOrder: 90,
);
```

Note that returning an empty array still renders an empty tab. To **hide** the tab entirely, conditionally call `registerTab()` rather than returning `[]`.

## Saving order and side-effects

The page’s `saveData()` walks every key in `$state`, persists each one, and then conditionally clears the route cache when permalink-related keys changed. **It does not run model events on `Setting`** beyond the standard Eloquent ones. If you need to react to a setting change, add a model observer to `Setting`:

```php
use Miran\Mksine\Models\Setting;

Setting::saved(function (Setting $setting) {
    if (str_starts_with($setting->key, 'acme_seo_')) {
        cache()->forget('acme.seo.computed');
    }
});
```

This is safer than tying behaviour to the Settings page lifecycle, which is admin-only and won’t fire when settings change via the `mks_setting()` helper or an Artisan command.

## Reading settings from your code

```php
$value  = mks_setting('acme_seo_meta_description');
$flag   = (bool) mks_setting('acme_seo_enable_og_tags');
$arr    = mks_setting('acme_seo_redirect_map');   // JSON-decoded if originally an array
```

The helper caches per request. To force a fresh read, query the model directly:

```php
\Miran\Mksine\Models\Setting::where('key', 'acme_seo_meta_description')->value('value');
```

## Testing

```php
app(SettingsTabManager::class)->clear();
```

Use `clear()` in test setup to start with a clean tab list. The manager is a singleton, so state persists between tests in the same Laravel boot.

## Honest limitations

- **Global key namespace.** No tab-scoped storage; tabs are a UI grouping only. Pick prefixes carefully and treat them like config keys you ship in v1 — renaming costs you a migration.
- **No validation cross-cutting tabs.** Each field’s Filament rules run on save, but there is no "validate the whole tab as a unit" hook.
- **No multi-locale store.** If you need translatable settings, store JSON keyed by locale yourself and decode on read.
- **No undo / draft.** Saving overwrites immediately.
- **No discoverability.** Tabs registered in code are invisible to non-developers. There is no admin "browse extensions" UI.
- **`SettingsTabManager` is per-request.** Re-register tabs on every boot.

## See also

- Reference: [`SettingsTabManager`](../../reference/facades-and-managers.md#settingstabmanager)
- Translations: [Translations workflow](../localization/translations.md)
