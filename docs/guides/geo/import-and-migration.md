---
title: Geo import and legacy migration
description: mks:geo:import (dr5hn countries, states, and cities) and mks:geo:migrate-legacy-iran for old ecom Iran tables.
order: 2
---

# Geo import and legacy migration

## Prerequisites

```bash
php artisan migrate   # includes packages/mksine geo tables
```

Every phase needs outbound HTTPS to `raw.githubusercontent.com` (the dr5hn dataset). No other database is needed.

## `mks:geo:import`

```
mks:geo:import
  [--only=countries|states|cities]
  [--country=IR]
  [--sync]
  [--locations-database=locations]
  [--locations-table=csv-cities]
```

Source: [`GeoImportCommand`](../../../src/Console/Commands/GeoImportCommand.php), [`GeoImportRunner`](../../../src/Services/Geo/GeoImportRunner.php), [`GeoImportService`](../../../src/Services/Geo/GeoImportService.php).

### What each phase imports

| Phase | Source | Notes |
| --- | --- | --- |
| **countries** | dr5hn `countries/countries.json` | Upsert into `geo_countries` by `id` |
| **states** | dr5hn `states/states.json` | Optional `--country=IR` filter |
| **cities** | dr5hn `cities/{ISO2}.json`, one file per country | Countries come from `geo_countries` (or `--country`). A country with no city file imports 0 cities. |

All ids are the dr5hn ids, and every phase upserts by primary key. Running the import again updates rows in place and never changes ids, so customer addresses in ecom that point at `geo_states` / `geo_cities` stay valid.

### One country (recommended for staging and most stores)

```bash
php artisan mks:geo:import --country=IR
```

With `--country` the import always runs in the command itself, without the queue. Iran takes about 40 seconds. At the end the command prints how many countries, states, and cities were imported and how many cities were skipped.

To import only cities, the states for that country must already be in `geo_states`:

```bash
php artisan mks:geo:import --only=states --country=IR
php artisan mks:geo:import --only=cities --country=IR
```

If the states are missing, `--only=cities` stops with a message telling you to run `--only=states` first.

### All countries

```bash
php artisan mks:geo:import --sync    # in the command, prints totals at the end
php artisan mks:geo:import           # on the queue: one job per country
```

Without `--sync` or `--country`, the command dispatches a queue job, prints the run ID and log path, and returns. The cities phase then runs as a batch with one job per country. Run a worker with enough memory, because the default 128 MB limit stops the worker after the first country:

```bash
php artisan queue:work --memory=1024
```

Add the connection and `--queue=` if you set `MKS_GEO_IMPORT_QUEUE_CONNECTION` / `MKS_GEO_IMPORT_QUEUE_NAME`. The command prints the exact worker command.

### Results, skipped cities, and exit codes

- Cities whose `state_id` is not in that country’s `geo_states` are skipped. The rest of the country is still imported, and the log lists the count and the missing state ids per country.
- If a country fails (download error, missing states), the import records it and continues with the next country.
- With `--sync` or `--country`, the command exits with **failure** and prints “Geo import finished with problems” when any country failed. Skipped cities alone are a warning: the command prints “Geo import completed with N cities skipped” and exits 0.
- On the queue, the batch’s last log line is “Geo import finished with problems” when a country job failed. Otherwise it is “Geo import completed”, followed by the number of skipped cities if there were any.

Log file per run: `storage/logs/mksine-geo-import/geo-import-{runId}.log` (`tail -f` it while the queue runs).

### Optional: cities from a MySQL `locations` database

Older installs read cities from a private MySQL copy of dr5hn. This is now used only when you pass `--locations-database`:

```bash
php artisan mks:geo:import --only=cities --country=IR \
  --locations-database=locations \
  --locations-table=csv-cities
```

Rows are read with:

```sql
SELECT * FROM `locations`.`csv-cities` WHERE country_code = ? ORDER BY id
```

Translations still come from the dr5hn city JSON. If the table cannot be read, the command fails with that error instead of skipping cities.

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
| Cities count = 0, “No states for XX” | States not imported for that country | `php artisan mks:geo:import --only=states --country=XX`, then `--only=cities --country=XX` |
| “N cities skipped” | dr5hn city rows point at a state id missing from `states.json` | Expected for a few countries; the log lists the state ids. Other cities of that country are imported. |
| Queue worker stops with “Memory limit exceeded” | `queue:work` default `--memory=128` | `php artisan queue:work --memory=1024`, or use `--sync` / `--country=` |
| States empty, download errors | Network blocked to GitHub | Run from CI/staging with egress; or mirror JSON locally (custom ops procedure) |
| Legacy migrate maps few cities | Name mismatch (`name` vs `native`) | Align geo row names or fix legacy data; manual FK patch |
| Admin shows no states | Country not in **enabled countries** | Settings → Geo |
