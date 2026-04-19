---
title: Plugin-to-plugin API
description: Exposing services from one plugin to another via the PluginApiInterface contract and a typed facade.
order: 18
---

# Plugin-to-plugin API

Plugins can call each other’s code, but doing it through `MyOtherPlugin\Services\Foo::class` directly is fragile — class names move, namespaces change, and now plugin A breaks when plugin B refactors. The [`PluginApiInterface`](../../reference/contracts.md#pluginapiinterface) contract gives you a stable surface to talk across plugins.

## When to use this

Use it when **another plugin** (not the host app) needs to call your plugin. If only the host app calls you, just bind a service in your `boot()` and document the binding name. The Public API contract is overhead you should accept only when you have at least two consumers.

If you have one consumer, ship a regular service. If you have multiple, expose them via the API.

## The contract

```php
namespace Miran\Mksine\Core\Plugins\Contracts;

interface PluginApiInterface
{
    public static function getFacadeClass(): ?string;
    public static function getContainerBinding(): string;
}
```

A plugin advertising a public API:

1. Implements `PluginApiInterface` on its `PluginInterface` class (or any class — the static methods are what matter).
2. Declares the facade in `plugin.php`:

   ```php
   'public_api' => [
       'facade' => 'MyPlugin\\Facades\\MyPlugin',
   ],
   ```

3. Binds the underlying service in `boot()`.

## A complete example

`{plugin_root}/my-plugin/src/Contracts/SearchService.php`:

```php
namespace MyPlugin\Contracts;

interface SearchService
{
    public function search(string $query, int $limit = 10): array;
}
```

`{plugin_root}/my-plugin/src/Services/InternalSearchService.php`:

```php
namespace MyPlugin\Services;

use MyPlugin\Contracts\SearchService;

class InternalSearchService implements SearchService
{
    public function search(string $query, int $limit = 10): array
    {
        // …
        return [];
    }
}
```

`{plugin_root}/my-plugin/src/Facades/MyPluginFacade.php`:

```php
namespace MyPlugin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array search(string $query, int $limit = 10)
 *
 * @see \MyPlugin\Contracts\SearchService
 */
class MyPluginFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'my-plugin.api';
    }
}
```

`{plugin_root}/my-plugin/src/MyPluginPlugin.php` — implementing both contracts:

```php
namespace MyPlugin;

use Miran\Mksine\Core\Plugins\Contracts\PluginApiInterface;
use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use MyPlugin\Contracts\SearchService;
use MyPlugin\Services\InternalSearchService;

class MyPluginPlugin implements PluginInterface, PluginApiInterface
{
    public function id(): string { return 'my-plugin'; }

    // …other lifecycle methods…

    public function boot(): void
    {
        app()->singleton(self::getContainerBinding(), fn () => new InternalSearchService());
        app()->bind(SearchService::class, fn () => app(self::getContainerBinding()));
    }

    public static function getFacadeClass(): ?string
    {
        return \MyPlugin\Facades\MyPluginFacade::class;
    }

    public static function getContainerBinding(): string
    {
        return 'my-plugin.api';
    }
}
```

## Calling the API from another plugin

Two patterns:

**A — via the facade** (the documented surface):

```php
use MyPlugin\Facades\MyPluginFacade;

$results = MyPluginFacade::search('milk', 25);
```

The consumer plugin must declare the dependency in `plugin.php`:

```php
'requires' => [
    'mksine'    => '^1.0',
    'my-plugin' => '^1.0',
],
```

**B — via the container binding** (when you don’t want a hard `use` reference):

```php
$results = app('my-plugin.api')->search('milk', 25);
```

This avoids the `use` import but you lose IDE autocompletion. Use it for opt-in integrations where the dependency is genuinely optional:

```php
if (app()->bound('my-plugin.api')) {
    $results = app('my-plugin.api')->search($query);
}
```

## Versioning the public API

Treat the facade signature as **public** (semver `MAJOR` for breaking changes). Practical rules:

- Adding methods to the facade → minor version bump of the plugin.
- Removing methods, changing parameter types, or moving the binding name → major version bump.
- The underlying `InternalSearchService` is **not** part of the contract — refactor freely as long as the facade signature stays.

Document the facade in your plugin’s `README.md` so consumers know what they may call. Anything not in that README is undefined and consumers should not rely on it.

## What the framework does (and doesn’t do) for you

- MKSine **does not** auto-register the facade alias. Either tell consumers to use the FQCN (`use MyPlugin\Facades\MyPluginFacade;`) or register an alias in your plugin’s `boot()`:

  ```php
  \Illuminate\Foundation\AliasLoader::getInstance()->alias('MyPlugin', \MyPlugin\Facades\MyPluginFacade::class);
  ```

- MKSine **does not** validate that consumers’ `requires` versions are satisfied — `requires` is informational. Consumer plugins must `app()->bound()` if integration is optional.

## Anti-patterns

- **Calling another plugin’s Eloquent model directly.** That couples you to its table layout. Use the facade and let the owning plugin abstract its persistence.
- **Returning Eloquent models from the facade.** Now the consumer is coupled to your model class. Return DTOs (`array`, `readonly class`, or a dedicated `Result` object).
- **Mutating shared state through the facade without events.** If consumer plugin A writes data, consumer plugin B should be able to react. Combine the facade with [event hooks](../hooks/event-hooks.md) — emit events and let listeners subscribe.

## See also

- [`PluginApiInterface` contract](../../reference/contracts.md#pluginapiinterface).
- [Lifecycle](lifecycle.md) — `boot()` is where bindings happen.
- [Event hooks](../hooks/event-hooks.md) — combining the API with cross-plugin events.
