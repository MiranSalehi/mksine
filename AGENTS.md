# AGENTS.md

## Cursor Cloud specific instructions

### Project overview

MKSine (`miran/mksine`) is a **Laravel + Filament 4 CMS Composer package** — not a standalone application. It is developed and tested in isolation using Orchestra Testbench; no host Laravel app is required for running the test suite.

### Key commands

| Task | Command |
|------|---------|
| Install PHP deps | `composer install` |
| Install JS deps | `npm install` |
| Run tests | `vendor/bin/pest` |
| Static analysis | `vendor/bin/phpstan analyse` |
| Code style check | `vendor/bin/pint --test` |
| Code style fix | `vendor/bin/pint` |
| Build JS assets | `npm run build:scripts` |
| Docs lint | `php scripts/lint-docs.php` |

See `composer.json` `scripts` section and `package.json` `scripts` for full list.

### Gotchas

- **No `composer.lock`**: This is intentional for a library package. `composer install` resolves dependencies fresh.
- **`phpstan-baseline.neon`**: Must exist (can be empty) or PHPStan will error. Create it with `touch phpstan-baseline.neon` if missing.
- **CSS build requires host app context**: `npm run build:styles` fails standalone because `resources/css/index.css` imports Filament CSS via `../../../../vendor/filament/...` (a path that only resolves when the package lives at `packages/mksine/` inside a Laravel app). `npm run build:scripts` works standalone.
- **`npm run build` and `npm run dev`** also run `bin/filament-assets.js` which requires a Laravel `artisan` file in a parent directory. These commands only work inside a host Laravel app context.
- **Tests use SQLite in-memory** via Orchestra Testbench; no database server is needed.
- **`laravel/fortify`** is required at runtime (used by `src/Models/User.php`) but is listed as a dev dependency. If `composer install` is run without `--no-dev`, it will be present.
