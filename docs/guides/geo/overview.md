---
title: Global geo system
description: Countries, states, and cities catalogue, settings, Filament admin, HTTP API, and how plugins consume geo.
order: 1
---

# Global geo system

MKSine core owns a **worldwide** geo catalogue (`geo_countries`, `geo_states`, `geo_cities`) and **store-wide preferences** (which countries are enabled, default checkout country, per-country address levels). Plugins such as **ecom** attach foreign keys and read settings through **`StoreGeoSettings`** and **`GeoResolver`** — they do not ship their own Iran-only tables.

Ecom-specific consumer notes: **`plugins/ecom/docs/guides/addresses-and-geo.md`**.

## Data model

| Table | Primary key | Parent | Notes |
| --- | --- | --- | --- |
| **`geo_countries`** | `id` (unsigned medium, **not auto-increment**) | — | `iso2`, `iso3`, `name`, `native`, `translations`, `is_active` |
| **`geo_states`** | `id` (unsigned medium) | `geo_country_id` | `code`, `name`, `native`, `source`, `is_visible`, `sort_order` |
| **`geo_cities`** | `id` (unsigned int) | `geo_state_id`, `geo_country_id` | Coordinates, `population`, `source`, `is_visible` |

IDs match the upstream **[dr5hn/countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database)** dataset on import. Models set **`$incrementing = false`** so Eloquent accepts preset IDs.

Migrations: **`packages/mksine/database/migrations/2026_06_06_100000_create_geo_tables.php`**.

## Settings (admin)

**System → Settings → Geo** (`Miran\Mksine\Filament\Pages\Settings\SettingsGeoPage`).

Persisted in the **`settings`** table via `mks_setting()`:

| Key | Type | Behaviour |
| --- | --- | --- |
| **`geo_enabled_countries`** | JSON array of ISO2 | Empty = all active countries in `geo_countries` |
| **`geo_default_country`** | ISO2 string | Default for checkout / admin address forms; falls back to first enabled country |
| **`geo_address_levels`** | JSON map `ISO2 → { show_state, show_city }` | Controls whether state/city selects appear for that country |

**Legacy fallback:** reads still honour old ecom keys (`ecom_enabled_countries`, `ecom_default_checkout_country`, `ecom_address_levels`) when the new keys are absent. The Geo settings page hydrates the form from legacy values on first open.

## Filament catalogue admin

Navigation group: **System** (same group as Settings and other system resources; labels under `mksine::geo.*`).

| Admin path | Purpose |
| --- | --- |
| **System → States** (`GeoStateResource`) | List/edit states; scoped to **enabled countries** from settings |
| **System → Settings → Geo** (`SettingsGeoPage`) | Enable countries, default country, per-country address levels |
| **Cities relation manager** (on state edit) | Manage cities for that state |

There is **no** `GeoCountryResource` in core (countries come from import; enable/disable via settings).

Policies: state permissions gate city management on the relation manager.

## Runtime services

| Class | Role |
| --- | --- |
| **`StoreGeoSettings`** | Read enabled countries, default country, address levels (`isStateVisible` / `isCityVisible`) |
| **`GeoResolver`** | Queries + `displayName()` with locale; `countriesForSelect`, `statesForSelect`, `citiesForState` (paginated search) |
| **`GeoAddressResolution`** (ecom) | Resolve FKs from text snapshots — lives in ecom, not core |

Resolve from the container:

```php
app(\Miran\Mksine\Services\Geo\StoreGeoSettings::class);
\Miran\Mksine\Services\Geo\GeoResolver::make();
```

## HTTP API (storefront / headless)

Routes (`packages/mksine/routes/geo.php`, middleware `web`):

| Route | Query params | Response |
| --- | --- | --- |
| **`GET /api/geo/countries`** | `locale` (optional) | `default_country`, `data[]` with `id`, `iso2`, `name`, `show_state`, `show_city` |
| **`GET /api/geo/states`** | `country_id`, `locale` | `data[]` with `id`, `name`, `code` |
| **`GET /api/geo/cities`** | `state_id`, `search`, `per_page` (10–100), `locale` | Paginated `data[]` with `id`, `name` |

Named routes: `mksine.geo.countries`, `mksine.geo.states`, `mksine.geo.cities`.

## Plugin integration checklist

1. Run migrations (core + plugin FK migrations).
2. **`php artisan mks:geo:import`** — see [Import and migration](import-and-migration.md).
3. Configure **Settings → Geo**.
4. On address save: persist **`geo_*` FKs** and keep **text snapshots** (`country`, `region`, `city`) in sync for exports and legacy rows.
5. For shipping/tax: pass **`geo_country_id` / `geo_state_id` / `geo_city_id`** on ship-to DTOs when available.

Do **not** add plugin-specific country tables for checkout; extend via FKs to `geo_*`.

## Related docs

- [Import and migration](import-and-migration.md) — `mks:geo:import`, locations DB, legacy Iran command
- [Commands reference](../../reference/commands.md) — command signatures
- [Adding settings tabs](../settings/adding-tabs.md) — how other plugins register settings (geo tab is core-owned)
