---
title: Registering Filament plugins from a plugin
description: How to add Filament Contracts\Plugin instances to the panel without touching the host PanelProvider.
order: 19
---

# Registering Filament plugins from a plugin

Sometimes a plugin needs to install a third-party **Filament plugin** (e.g. `bezhansalleh/filament-shield`, `awcodes/filament-tiptap-editor`) onto the admin panel. Doing it by editing the host’s `AppServiceProvider` or `AdminPanelProvider` defeats the point of plugin isolation. The [`RegistersFilamentPlugins`](../../reference/contracts.md#registersfilamentplugins) contract is the supported way.

## The contract

```php
namespace Miran\Mksine\Core\Plugins\Contracts;

use Filament\Contracts\Plugin;
use Filament\Panel;

interface RegistersFilamentPlugins
{
    /**
     * @return array<int, Plugin>
     */
    public function filamentPlugins(Panel $panel): array;
}
```

Implement it on your plugin’s `PluginInterface` class. MKSine collects the returned `Plugin` instances when building the panel and registers them as if they had been added in the host’s panel provider.

## Example: registering Filament Shield from a plugin

```php
namespace MyPlugin;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use Miran\Mksine\Core\Plugins\Contracts\RegistersFilamentPlugins;

class MyPluginPlugin implements PluginInterface, RegistersFilamentPlugins
{
    public function id(): string { return 'my-plugin'; }

    // …other lifecycle methods…

    /**
     * @return array<int, Plugin>
     */
    public function filamentPlugins(Panel $panel): array
    {
        return [
            FilamentShieldPlugin::make()
                ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3]),
        ];
    }

    public function boot(): void
    {
        // No panel work here — `filamentPlugins()` already covers it.
    }
}
```

## Constraints and rules

- **Idempotency:** `filamentPlugins()` is called **once per panel build**. Returning a singleton instance (`Plugin::make()`) is fine.
- **No side effects in the method itself.** Don’t bind container services or run Artisan commands from inside `filamentPlugins()`. Use `boot()` for that — the method is purely a constructor for the plugin objects.
- **Use the `$panel` argument** if your registration depends on the panel ID:

  ```php
  if ($panel->getId() === 'admin') {
      return [FilamentShieldPlugin::make()];
  }

  return [];
  ```

- The Filament plugin classes you return must already be **autoloadable** at panel-build time. Either the host app already requires the underlying composer package, or your plugin vendors it locally and registers PSR-4 in `boot()` (see [Composer and publishes presets](composer-and-publishes.md)).
- If the package is missing at runtime, `filamentPlugins()` should **fail closed**: return `[]` and log a warning, rather than throwing and breaking the panel boot.

## Pitfalls

- **Returning anonymous classes** — the panel cache may not handle them well. Use named classes from the third-party package.
- **Adding a Filament plugin that conflicts with what the host already added.** There is no de-dupe; the same plugin registered twice may produce duplicate menu items, double permissions, etc. Coordinate with the host app, or check `Filament::hasPlugin('shield')`-style guards if exposed by the third party.
- **Hard-required third-party Filament plugins inside an MKSine plugin** that other host apps may not want. Make installation explicit in your plugin’s README ("Activating this plugin will register Filament Shield"), or feature-flag it.

## See also

- [`RegistersFilamentPlugins` contract](../../reference/contracts.md#registersfilamentplugins).
- [Filament resources](filament-resources.md) — the simpler, more common case.
- [Plugin lifecycle](lifecycle.md) — where `filamentPlugins()` fits in the boot sequence.
