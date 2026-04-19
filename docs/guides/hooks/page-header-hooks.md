---
title: Page header hooks
---

# Page header hooks

Page header hooks let you append actions to the header of a Filament resource page (the `List`, `Create`, `Edit`, `View` pages). Same shape as resource hooks: **runtime-only**, no discovery, no DB row.

## Where they fire

Generated `*ListPosts`, `*EditPost`, etc. expose:

```php
protected function getHeaderActions(): array
{
    return app(\Miran\Mksine\Core\Hooks\PageHookManager::class)
        ->applyHeaderActions('post.list', [
            CreateAction::make(),
        ]);
}
```

`PageHookManager::applyHeaderActions($pageName, $actions)` walks every callback registered for `$pageName`, in registration order, feeding each one the **current array** and using its return value if it is an array (anything else is dropped silently).

## Adding an action

```php
use Filament\Actions\Action;
use Miran\Mksine\Core\Hooks\Hooks;

public function boot(): void
{
    Hooks::extendPageHeaderActions('post.list', function (array $actions) {
        $actions[] = Action::make('exportCsv')
            ->label('Export CSV')
            ->action(fn () => /* your export logic */ null)
            ->icon('heroicon-o-arrow-down-tray');

        return $actions;
    });
}
```

## Naming convention

Pages use `'<resource>.<page>'`:

| Filament page  | Hook name        |
| -------------- | ---------------- |
| List           | `post.list`      |
| Create         | `post.create`    |
| Edit           | `post.edit`      |
| View           | `post.view`      |

Adopted by the package’s scaffolds. Plugins should follow the same convention. The string is whatever the resource passes to `applyHeaderActions()` — there is no central registry.

## Authorization

Header actions render whatever the action’s `->visible()` / `->authorize()` callbacks return. The hook itself does **no** permission filtering. If your action needs `Filament\Forms\Concerns\InteractsWithForms` or similar, build a full `Action` subclass; closures are fine for simple cases.

## Limits

- No discovery, no admin toggle, no DB row.
- No priority — callbacks run in registration order.
- No exception isolation. A throwing callback crashes the page.

## See also

- [Two hook families](overview-two-families.md)
- [Resource hooks](resource-hooks.md)
- Reference: [`PageHookManager`](../../reference/facades-and-managers.md#pagehookmanager)
