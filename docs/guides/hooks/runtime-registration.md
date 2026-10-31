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
| `Hooks::beforeFormComponent` / `afterFormComponent` / `replaceFormComponent` | `FormHookManager` | Named slot insert / replace / hide on a form anchor |
| `Hooks::extendTable($tableName, fn ($table) => $table)`         | `TableHookManager`     | Closure that returns a modified `Table`      |
| `Hooks::extendTableColumns/Actions/BulkActions/Filters(...)`    | `TableHookManager`     | Same closure shape, different scheduling bucket |
| `Hooks::extendResourceRelations($name, fn ($relations))`        | `ResourceHookManager`  | Closure that returns a modified relations array |
| `Hooks::extendResourceWidgets($name, fn ($widgets))`            | `ResourceHookManager`  | Closure that returns a modified widgets array |
| `Hooks::extendPageHeaderActions($name, fn ($actions))`          | `PageHookManager`      | Closure that returns a modified actions array |
| `Hooks::addFilter($name, fn ($value, ...$args) => $value)`      | `HookFilterRegistry`   | Priority-ordered mutable filter chain         |
| `Hooks::filter($name, $value, ...$args)`                         | `HookFilterRegistry`   | Apply all filters registered for `$name`      |
| `Hooks::removeFilter($name, $handler)`                          | `HookFilterRegistry`   | Remove a filter by id or callable             |
| `Hooks::hasFilter($name)`                                       | `HookFilterRegistry`   | Whether `$name` has any filter                |
| `Hooks::addShortcode($tag, $handler, $priority, $catalog)`      | `ShortcodeRegistry`    | Register a storefront content shortcode       |
| `Hooks::addLivewireShortcode($tag, $componentClass, ...)`        | `ShortcodeRegistry`    | Register a Livewire-backed interactive shortcode |

`Hooks::register()` writes to the `HookRegistry` (the in-memory list of event-name → listener-class bindings). It does **not** populate `mks_hooks`. To get a DB row, the listener class must also be picked up by `mks:discover`.

### Runtime filters

`Hooks::addFilter()` / `Hooks::filter()` implement a WordPress-style filter chain in memory (lower filter priority runs first). Use this when callbacks must **return a modified value** — for example [`frontend_admin_bar.items`](../storefront/frontend-admin-bar.md) (menu item list) or ecom checkout payment-method lists.

```php
Hooks::addFilter('frontend_admin_bar.items', function (array $items, $context, $panel): array {
    $items[] = new FrontendAdminBarItem(label: 'My link', url: '/admin/foo');
    return $items;
}, priority: 20);

$items = Hooks::filter('frontend_admin_bar.items', [], $context, $panel);
```

Filters are runtime-only (no `mks_hooks` row, no admin toggle). See [Frontend admin bar](../storefront/frontend-admin-bar.md) for the full item/dropdown contract.

An exception in a filter propagates to the caller. Filters guard values such as prices and payment methods, so a failure there must not pass silently. Theme actions behave differently: see [Action hooks for templates](../themes/views-and-layouts.md#action-hooks-for-templates-themedoaction).

### Removing hooks

Filters and theme actions can be removed. Most handlers are closures, and another plugin never holds that closure, so give a handler a name with the last parameter:

```php
Hooks::addFilter('ecom.checkout.available_payment_methods', $callback, 10, 'acme.hide_cod');
theme_add_action('home.before_hero', $callback, 10, 'acme.promo_banner');

// In another plugin
Hooks::removeFilter('ecom.checkout.available_payment_methods', 'acme.hide_cod');
theme_remove_action('home.before_hero', 'acme.promo_banner');
```

Prefix ids with your plugin name. An id only has to be unique on its hook.

**Adding an id twice** replaces the first entry. The new callback takes the old one's place among handlers of the same priority, and uses the new priority if it differs.

**Removal does not depend on boot order.** Removing by id also remembers that id on that hook, and a later add with the same id is ignored. A plugin that boots first can still remove a handler from one that boots later. The block lasts for the life of the registry. To replace another plugin's handler, remove its id and add yours under a different id.

`removeFilter()` and `theme_remove_action()` match like this:

| `$handler`      | Matches                                                                  |
| --------------- | ------------------------------------------------------------------------ |
| A string        | Handlers with that id. If there are none, string callables with that name. |
| Any other callable | The same callable by strict identity (`===`). All matches are removed. |

- A string is always remembered as a removed id, even when it matched a string callable.
- Only ids are blocked. A handler added without an id is never ignored, so removing a closure or a string callable affects only what is registered at that moment.
- `strlen(...)` and `$this->handle(...)` create a new closure each time, so they do not match an earlier registration. Keep the reference or use an id.
- Removing something that does not exist does nothing.
- Removal inside a running hook takes effect on the next run. The current run finishes with the handlers it started with.

Use `Hooks::hasFilter($name)` or `ThemeActionManager::hasAction($hook)` to check whether a hook has any handler left.

### Shortcodes

`Hooks::addShortcode()` registers WordPress-style tags processed by `mks_render_content()`. Pass an optional `ShortcodeCatalogEntry` so the tag appears in the CKEditor **Insert shortcode** picker.

`Hooks::addLivewireShortcode()` mounts a Livewire component at render time (not cacheable). See [Shortcodes](../content/shortcodes.md).

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

Filters and theme actions behave the same way without an id. With an id, a second add replaces the first, so the handler runs once. See [Removing hooks](#removing-hooks).

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
- [Frontend admin bar](../storefront/frontend-admin-bar.md) — `Hooks::addFilter('frontend_admin_bar.items', …)`
- [SEO analysis](../seo/analysis.md) — `Hooks::addFilter(SeoAnalyzer::FILTER_CHECKS, …)`
- [Admin menu editor](../settings/admin-menu.md) — `Hooks::addFilter('mksine.admin.navigation.groups', …)`
- [Shortcodes](../content/shortcodes.md) — `Hooks::addShortcode()`
