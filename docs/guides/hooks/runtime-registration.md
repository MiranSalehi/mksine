---
title: Runtime registration
---

# Runtime registration

Runtime hooks are registered in code (typically a plugin’s `boot()` method) and live only in memory for the duration of the request. Use them when:

- The behaviour depends on host configuration that is not known at deploy time.
- You need to wire a closure (no class file).
- You are prototyping; you can promote to a class-based listener later.

If you want an admin toggle, a DB row, or a priority override surface, use a [discovery event hook](event-hooks.md) instead.

## The four entry points

`Hooks::` is a thin static facade over four manager singletons. All registration happens here.

| Call                                                            | Manager                | What it registers                            |
| --------------------------------------------------------------- | ---------------------- | -------------------------------------------- |
| `Hooks::register($eventName, $listenerClass, $priority)`        | `HookManager`          | Event listener class for an event name       |
| `Hooks::extendForm($formName, fn ($schema) => $schema)`         | `FormHookManager`      | Closure that returns a modified `Schema`     |
| `Hooks::extendTable($tableName, fn ($table) => $table)`         | `TableHookManager`     | Closure that returns a modified `Table`      |
| `Hooks::extendTableColumns/Actions/BulkActions/Filters(...)`    | `TableHookManager`     | Same closure shape, different scheduling bucket |
| `Hooks::extendResourceRelations($name, fn ($relations))`        | `ResourceHookManager`  | Closure that returns a modified relations array |
| `Hooks::extendResourceWidgets($name, fn ($widgets))`            | `ResourceHookManager`  | Closure that returns a modified widgets array |
| `Hooks::extendPageHeaderActions($name, fn ($actions))`          | `PageHookManager`      | Closure that returns a modified actions array |

`Hooks::register()` writes to the `HookRegistry` (the in-memory list of event-name → listener-class bindings). It does **not** populate `mks_hooks`. To get a DB row, the listener class must also be picked up by `mks:discover`.

## The right place to call them

Always inside a `boot()` method that runs on every request — typically the plugin’s `boot()`:

```php
namespace Acme\MyPlugin;

use Miran\Mksine\Core\Plugins\Contracts\PluginInterface;
use Miran\Mksine\Core\Hooks\Hooks;

final class MyPlugin implements PluginInterface
{
    public function boot(): void
    {
        Hooks::register('post.created', Listeners\NotifySlackListener::class, 50);

        Hooks::extendForm('post.form', function ($schema) {
            $existing = method_exists($schema, 'getComponents') ? $schema->getComponents() : [];

            return $schema->components([
                ...$existing,
                \Filament\Forms\Components\TextInput::make('utm_source')->maxLength(64),
            ]);
        });
    }
}
```

Do **not** call them in:

- A migration (they will not survive the request).
- A controller action (they only register for that one request).
- A constructor (the manager singletons may not be wired yet, depending on container state).

If you need to register from a host-app service provider (no plugin involved), put the calls inside `boot()`, after the package has booted. The package binds the manager singletons in `MksineServiceProvider::register()`, so they are available by the time host providers boot.

## Conditional registration

This is the main reason runtime registration exists:

```php
public function boot(): void
{
    if (config('app.env') === 'production') {
        Hooks::register('user.registered', Listeners\SendWelcomeEmail::class, 10);
    }

    if (config('myplugin.features.audit_log')) {
        Hooks::register('post.updated', Listeners\AuditPostUpdate::class, 0);
    }
}
```

A discovery hook would always show up in `mks_hooks` regardless of feature flags. A runtime hook only registers when the flag is on, which keeps `mks_hooks` clean and avoids confusing operators.

## Closures vs class-based runtime registration

`Hooks::register('event.name', SomeListener::class, 10)` references a **class**. The dispatcher will resolve it from the container per request. This is the recommended shape even for runtime registration — it lets you unit-test the listener in isolation.

`Hooks::extendForm`, `extendTable`, `extendResource…`, `extendPageHeaderActions` accept **callables**, which can be closures. Closures are fine for trivial extensions. For anything non-trivial, write an invokable class:

```php
final class AppendUtmField
{
    public function __invoke($schema)
    {
        // …
        return $schema;
    }
}

Hooks::extendForm('post.form', new AppendUtmField);
```

## Idempotency and double-registration

Every call to `Hooks::register()` or `Hooks::extend*()` **appends** to the in-memory list. There is no de-duplication. If a plugin’s `boot()` runs twice in the same request (it shouldn’t, but if you misuse `app()->make()` somewhere it can), the listener fires twice.

Defensive pattern when you cannot fully trust the boot path:

```php
private static bool $registered = false;

public function boot(): void
{
    if (self::$registered) {
        return;
    }
    self::$registered = true;

    Hooks::register(/* … */);
}
```

Don’t reach for this by default — it papers over real ordering bugs. Fix the boot path first.

## Caveats vs discovery hooks

| Property                                      | Runtime    | Discovery (event)        |
| --------------------------------------------- | ---------- | ------------------------ |
| Persisted in `mks_hooks`                      | No         | Yes                      |
| Toggleable at runtime                         | No         | Yes (`is_enabled`)       |
| Priority overrideable per environment         | No         | Yes (`priority` column)  |
| Must boot on every request                    | Yes        | Yes (registration is the same; only the toggle/priority differ) |
| Fires when running `php artisan` if SP boots  | Yes        | Yes                      |

The takeaway: **runtime hooks are code, discovery hooks are configuration**. Choose based on _who_ should be able to turn the behaviour off.

## See also

- [Two hook families](overview-two-families.md)
- [Event hooks](event-hooks.md)
- [Form hooks](form-hooks.md)
- [Table hooks](table-hooks.md)
- [Resource hooks](resource-hooks.md)
- [Page header hooks](page-header-hooks.md)
