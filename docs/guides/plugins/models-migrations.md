---
title: Models and migrations
description: Generators, naming conventions, dependent migrations, and the rules around uninstall.
order: 14
---

# Models and migrations

Plugin models live under `{plugin_root}/{id}/src/Models/`, and migrations under `{plugin_root}/{id}/database/migrations/`. MKSine treats both directories as opt-in: missing directories are simply skipped.

## Generate a model

```bash
php artisan mks-plugin:make-model my-plugin Product --migration
```

Source: [`PluginMakeModelCommand`](../../../src/Console/Commands/PluginMakeModelCommand.php). Behaviour:

- Validates that `my-plugin` exists and has a namespace declared.
- Creates `src/Models/Product.php` extending `Illuminate\Database\Eloquent\Model`.
- Sets `$table` using the convention `mks_{plugin_id_with_underscores}_{snake_plural(name)}`. Example: `my-plugin` + `Product` → `mks_my_plugin_products`.
- With `-m`/`--migration`, also creates `database/migrations/{timestamp}_create_{table}_table.php` with an empty `Schema::create()` body.

Edit the migration before running it. The stub is intentionally minimal — add columns, indexes, foreign keys yourself.

## Naming conventions (and why they exist)

| Element | Convention | Reason |
|---------|-----------|--------|
| Table prefix | `mks_` | Easy to grep and to identify MKSine tables in DB tooling. |
| Plugin slot | `{plugin_id_with_underscores}_` | Prevents collisions between plugins that ship a `posts` table. |
| Body | `snake_plural(model)` | Standard Laravel. |

Override the table name only when you have a real migration constraint (e.g. integrating with an existing table from a previous system). Document the exception in your plugin README — silent overrides bite during cross-plugin debugging.

## Migration discovery

`PluginManager` runs `php artisan migrate --path={plugin}/database/migrations --force` on:

- `mks-plugin:install` (initial install).
- `mks-plugin:migrate {plugin?}` (repeatable; safe to call after pulling new migrations).

Migrations under your plugin therefore behave **exactly like** any host-app migration:

- Order is determined by filename timestamp.
- Re-running `migrate` is a no-op for already-applied files.
- Rolling back is via `migrate:rollback --path=…` (the package exposes [`PluginLifecycle::rollbackMigrations()`](../../../src/Core/Plugins/PluginLifecycle.php) but no Artisan wrapper — call it from a custom command if you need it).

Add `php artisan mks-plugin:migrate` to your deploy script after `php artisan migrate`.

## Cross-plugin foreign keys

If your plugin needs to reference another plugin’s table (e.g. `mks_my_plugin_orders.user_id` → `users.id`):

- Inside the **same** plugin, use `$table->foreignId('user_id')->constrained('users')` (the `users` table exists by the time MKSine itself migrates).
- For another **plugin’s** table, declare a soft dependency in `plugin.php`:

  ```php
  'requires' => [
      'mksine'              => '^1.0',
      'mks-other-plugin'    => '^1.0',
  ],
  ```

  …and rely on install order: install the dependency first. The package does **not** enforce dependency order yet — the `requires` block is documentation. If you need hard ordering, gate your migration with `if (! Schema::hasTable('mks_other_plugin_things')) { return; }` and publish a separate migration that runs after activation.

## Plugin uninstall and data deletion

The contract:

- `mks-plugin:uninstall {plugin}` (no flag): keep tables, remove the plugin row.
- `mks-plugin:uninstall {plugin} --delete-data`: tells your plugin "delete everything you own".

The package will **not** automatically drop your tables. Implement this in `PluginInterface::uninstall(bool $deleteData = false)`:

```php
use Illuminate\Support\Facades\Schema;

public function uninstall(bool $deleteData = false): void
{
    if (! $deleteData) {
        return;
    }

    Schema::dropIfExists('mks_my_plugin_products');
    Schema::dropIfExists('mks_my_plugin_orders');

    \Illuminate\Support\Facades\DB::table('migrations')
        ->where('migration', 'like', '%create_mks_my_plugin_%')
        ->delete();
}
```

The `migrations`-table cleanup is important — otherwise reinstalling the plugin will skip the migrations and your tables stay missing.

## Soft delete, casts, and traits

The generated stub is bare:

```php
class Product extends Model
{
    protected $table = 'mks_my_plugin_products';

    protected $fillable = [];

    protected function casts(): array
    {
        return [];
    }
}
```

Add what you need (`HasUuids`, `SoftDeletes`, `LogsActivity`, …). Two notes:

- **Activity log:** if your plugin vendors `spatie/laravel-activitylog` (committed under `vendor/` for ZIP deploys), MKSine will register its PSR-4 automatically (see [`PluginManager::registerPluginActivitylogAutoload()`](../../../src/Core/Plugins/PluginManager.php)). Do not require the plugin’s full `vendor/autoload.php` — it pulls duplicate Symfony/Laravel and breaks the host autoload order.
- **`HasFactory`:** factories are great for plugin tests, but ship the factory under `database/factories/` and add it to your plugin’s `composer.json` `autoload-dev` block so it does not load in production.

## Seeders

`mks-plugin:install` does not run seeders automatically. Two patterns that work:

1. **Inline seed in `install()`**: fastest, fine for small reference data (e.g. default categories). Use `firstOrCreate()` so it is idempotent.
2. **Custom seeder + Artisan call**: ship `database/seeders/{Plugin}Seeder.php`, register a console command in your plugin’s `boot()` that runs `Artisan::call('db:seed', ['--class' => MyPluginSeeder::class])`.

Avoid hardcoding seed calls in `boot()` — they will run on every request.

## Pitfalls

- **Forgetting to update `migrations` table on `--delete-data`** leaves orphans that block reinstall.
- **Naming a table without the `mks_{plugin}_` prefix** breaks DB-tier troubleshooting; you lose easy grep across plugins.
- **Putting models outside `src/Models/`**: Laravel doesn’t care, but the model generator (`mks-plugin:make-model`) writes to `src/Models/` unconditionally. Consistency saves time.
- **Heavy migrations during `install()`**: install-time crashes leave the plugin in a half-applied state. Keep migrations small and idempotent.

## See also

- [Lifecycle](lifecycle.md) — when migrations actually run.
- [Composer and publishes presets](composer-and-publishes.md) — vendoring third-party migrations into a plugin.
- [Commands → `mks-plugin:make-model`](../../reference/commands.md#mks-pluginmake-model)
