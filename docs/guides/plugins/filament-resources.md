---
title: Filament resources from plugins
description: How plugin resources are auto-registered, how forms/tables get hook-extensible by default, and how to keep navigation and policies sane.
order: 12
---

# Filament resources from plugins

Plugins ship Filament 4 resources from `src/Filament/Resources/` and they are picked up automatically — no panel-provider edits required. This page covers what the framework does, what the scaffold gives you, and where to slot in customisations safely.

## Auto-registration

When a plugin is **active**, MKSine asks the manifest for `filamentResourcesPath()` (default: `src/Filament/Resources/` if the directory exists). The MKSine `Panel`/Filament integration scans the namespace returned by `filamentResourcesNamespace()` (= `{namespace}\Filament\Resources`) and registers every resource class under it.

Two consequences:

- **Namespace must be set** in `plugin.php` (or implicitly via `PluginInterface::namespace()`). Without it, MKSine logs `Plugin '{id}' does not have a namespace defined` and skips registration.
- **Folder layout matters.** Each resource lives at `src/Filament/Resources/{Name}Resource/{Name}Resource.php`. Pages, schemas, and tables nest underneath. Don’t flatten — generators and discovery rely on this.

## What the scaffold generates

```bash
php artisan mks-plugin:make-resource my-plugin Item --model=Item
```

Generates ([`PluginMakeResourceCommand`](../../../src/Console/Commands/PluginMakeResourceCommand.php)):

```
src/Filament/Resources/ItemResource/
├── ItemResource.php
├── Schemas/ItemForm.php
├── Tables/ItemTable.php
└── Pages/
    ├── ListItems.php          (extends MksineListRecords)
    ├── CreateItem.php         (extends CreateRecord)
    └── EditItem.php           (extends EditRecord)
```

Notes:

- `ItemForm::configure()` ends with `FormHookManager::apply('Item.form', $schema)` — every form is hook-extensible by default.
- `ItemTable::configure()` ends with `TableHookManager::apply('Item.table', $table)` — same story for tables.
- `ListItems` extends `Miran\Mksine\Filament\Resources\Pages\MksineListRecords`, which calls `ResourceHookManager::applyWidgets('Item.resource', …)` and `PageHookManager::applyHeaderActions('Item.list', …)` for you. **Use this base class** instead of stock `ListRecords` if you want hooks to apply.
- `EditItem` and `CreateItem` extend stock Filament base classes. If you want resource hooks on those, swap to MKSine equivalents (or call the managers manually in `getHeaderActions()`).

The hook key is `{ResourceName}` literally — `Item.form`, `Item.table`. If you rename, **update the manager call**.

## Models and resource binding

The generator imports `{namespace}\Models\{ModelName}`. If your model lives elsewhere (e.g. published to `app/Models/`), edit `ItemResource::$model` after scaffolding.

```php
protected static ?string $model = \App\Models\Item::class;
```

This is the only place MKSine cares about the model class — table queries, form binding, and navigation labels all go through it.

## Navigation, labels, and slugs

The scaffold sets:

- `$slug = '{plural-lowercased}'` — the URL path (e.g. `/admin/items`).
- `$navigationIcon = 'heroicon-o-rectangle-stack'` — change to whatever Heroicon (or custom icon) you ship.
- `getNavigationLabel()`, `getModelLabel()`, `getPluralModelLabel()` — translated via `__()`. Add the keys to `resources/lang/{locale}/{plugin-namespace}.php`.

For navigation grouping, use Filament’s standard `static ?string $navigationGroup = 'Plugins';` — MKSine does not impose a group automatically.

## Permissions (Filament Shield)

Generated resources are not automatically permissioned. After scaffolding:

```bash
php artisan shield:generate --resource=ItemResource
```

Then re-assign roles. If you are inside CI, this should be part of your install script. See [Shield and policies](../auth/shield-and-policies.md).

## Hook-extending a plugin’s own resource

The generated form/table calls `apply()` against the global `FormHookManager` / `TableHookManager`. So **another plugin** (or your app code) can extend the form by registering callbacks before render, e.g. in another plugin’s `boot()`:

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Filament\Forms\Components\TextInput;

Hooks::extendForm('Item.form', function ($schema) {
    return $schema->components([
        TextInput::make('sku')->label(__('SKU')),
    ]);
});
```

This is the entire reason `apply()` is wired into the scaffold by default. See [Form hooks](../hooks/form-hooks.md) for callback rules and ordering.

## Custom pages on a resource

Add a page class under `Pages/`, register it in `ItemResource::getPages()`, and route as usual:

```php
public static function getPages(): array
{
    return [
        'index'  => Pages\ListItems::route('/'),
        'create' => Pages\CreateItem::route('/create'),
        'edit'   => Pages\EditItem::route('/{record}/edit'),
        'audit'  => Pages\ItemAudit::route('/{record}/audit'),
    ];
}
```

Page hooks (`PageHookManager::applyHeaderActions('Item.audit', …)`) use the convention `{ResourceName}.{view-key}`, e.g. `Item.list`, `Item.edit`, `Item.audit`.

## Sharing resources across plugins

Two plugins must **never** declare the same resource class (Filament will register the second and the first becomes unreachable). Patterns that scale:

- One plugin "owns" the resource. Other plugins extend it through hooks instead of re-implementing.
- Or: use **separate** resources backed by the same model, but with different slugs and `getEloquentQuery()` scopes (e.g. `Order` vs `RefundedOrder`).

If you genuinely need to swap one plugin’s resource for another’s, deactivate the original and run `mks-plugin:discover --clear`. Filament will not hot-swap registered classes.

## Pitfalls

- **Forgetting `MksineListRecords`** on a `Pages/List…` class silently drops resource widget hooks. Symptom: other plugins call `Hooks::extendResourceWidgets('Item.resource', …)` and nothing happens.
- **Using `Forms\Components\Builder`** without enabling `mksine.features.page_builder` if you are wiring it into the page builder — they are independent. The page builder feature flag only gates MKSine’s own `PageBuilderField`.
- **Renaming the resource folder** without updating namespaces breaks discovery silently — `Panel::discoverResources()` walks the directory and matches PSR-4. Re-discover after moves: `mks-plugin:discover --clear`.

## See also

- [Filament pages and widgets](filament-pages-widgets.md) — same idea, applied to `Pages/` and `Widgets/`.
- [Form hooks](../hooks/form-hooks.md), [Table hooks](../hooks/table-hooks.md), [Resource hooks](../hooks/resource-hooks.md) — the extension surface.
- [Registering Filament plugins](filament-plugins.md) — when you need to add a `Filament\Contracts\Plugin` instance, not a resource.
