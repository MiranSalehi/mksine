---
title: Geo import and legacy migration
description: mks:geo:import, cities locations database, and mks:geo:migrate-legacy-iran for old ecom Iran tables.
order: 2
---

# Geo import and legacy migration

## Prerequisites

```bash
php artisan migrate   # includes packages/mksine geo tables
```

Network access is required for **countries** and **states** (HTTP fetch from dr5hn GitHub raw JSON). **Cities** additionally require a local MySQL **`locations`** database — see below.

## `mks:geo:import`

```
mks:geo:import
  [--only=countries|states|cities]
  [--country=IR]
  [--locations-database=locations]
  [--locations-table=csv-cities]
```

Source: [`GeoImportCommand`](../../../src/Console/Commands/GeoImportCommand.php), [`GeoImportService`](../../../src/Services/Geo/GeoImportService.php).

### What each phase imports

| Phase | Source | Notes |
| --- | --- | --- |
| **countries** | dr5hn `countries.json` | Upsert into `geo_countries` by `id` |
| **states** | dr5hn `states.json` | Optional `--country=IR` filter |
| **cities** | MySQL `` `locations`.`csv-cities` `` | Translations merged from dr5hn per-country city JSON; skipped if locations DB unreachable |

### Typical first-time run

```bash
php artisan mks:geo:import
```

Iran-only staging (faster):

```bash
php artisan mks:geo:import --country=IR
php artisan mks:geo:import --only=cities --country=IR
```

Re-import is **idempotent** (upsert by primary key).

### Cities: `locations` database

City rows are read with:

```sql
SELECT * FROM `locations`.`csv-cities` [WHERE country_code = ?] ORDER BY id
```

Configure the connection via command options if your database or table name differs:

```bash
php artisan mks:geo:import --only=cities \
  --locations-database=locations \
  --locations-table=csv-cities
```

If the table is missing or the connection fails, the cities phase imports **0 rows** without failing the whole command — verify counts after import.

**Operator action:** provision the `locations` database from the dr5hn CSV/SQL distribution (or your organisation’s mirror) before expecting full city coverage.

## `mks:geo:migrate-legacy-iran`

```
mks:geo:migrate-legacy-iran
```

Source: [`GeoMigrateLegacyIranCommand`](../../../src/Console/Commands/GeoMigrateLegacyIranCommand.php).

One-time migration when legacy ecom tables still exist:

- `mks_ecom_iran_provinces` / `mks_ecom_iran_cities`
- FK columns `iran_province_id` / `iran_city_id` on customer addresses
- JSON `iran_geo` on shipping methods
- Legacy zone location rows

The command:

1. Maps legacy province/city names to **`geo_states` / `geo_cities`** for Iran (`iso2 = IR`).
2. Writes **`geo_country_id` / `geo_state_id` / `geo_city_id`** on **`mks_ecom_customer_addresses`**.
3. Converts **`iran_geo`** on shipping methods to **`geo_scope`** JSON.
4. Updates **`mks_ecom_shipping_zone_locations`** geo FKs.

**Requires:** `mks:geo:import` completed and Iran present in `geo_countries`. If `mks_ecom_iran_provinces` is already dropped, the command exits successfully with “No legacy Iran geo tables found.”

Run **before** dropping legacy Iran tables, **after** geo import.

## Post-import verification

```bash
php artisan tinker --execute 'echo \Miran\Mksine\Models\GeoCountry::count();'
php artisan tinker --execute 'echo \Miran\Mksine\Models\GeoState::whereHas("country", fn ($q) => $q->where("iso2", "IR"))->count();'
```

Admin: **Settings → Geo** — enable `IR` (or your markets), set default country and address levels.

Ecom: **`plugins/ecom/docs/guides/addresses-and-geo.md`**, Woo import **`plugins/ecom/docs/operations/woo-import.md`**.

## Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Cities count = 0 | `locations` DB not provisioned | Import dr5hn cities into `` locations.csv-cities `` or use `--country=` for partial import |
| States empty | Network blocked to GitHub | Run from CI/staging with egress; or mirror JSON locally (custom ops procedure) |
| Legacy migrate maps few cities | Name mismatch (`name` vs `native`) | Align geo row names or fix legacy data; manual FK patch |
| Admin shows no states | Country not in **enabled countries** | Settings → Geo |
