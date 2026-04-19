---
title: Table hooks
---

# Table hooks

Table hooks let you mutate Filament tables (columns, filters, actions, bulk actions) from outside the resource that owns the table. Same dispatch model as [form hooks](form-hooks.md): one in-memory manager, two registration styles.

## How a table gets hooked

Resources scaffolded by `mks-plugin:make-resource` end with:

```php
public static function table(Table $table): Table
{
    $table = $table
        ->columns([
            // …default columns…
        ])
        ->filters([…])
        ->actions([…])
        ->bulkActions([…]);

    return app(\Miran\Mksine\Core\Hooks\TableHookManager::class)
        ->apply('post.table', $table);
}
```

`TableHookManager::apply()` runs registered callables for `'post.table'` in this fixed bucket order:

1. `extend()` callbacks (full-table, most flexible)
2. `extendColumns()` callbacks
3. `extendActions()` callbacks
4. `extendBulkActions()` callbacks
5. `extendFilters()` callbacks

Within each bucket, callbacks run in registration order.

> **Reality check.** The bucket names are documentation-only — every `extend*` method takes a `callable($table): Table` signature and receives the **whole `Table` object**. There is no array-of-columns or array-of-actions argument despite what the `Hooks::extendTableColumns()` PHPDoc historically implied. Treat the bucket names as a **scheduling hint**, not a typed API. If you need to add a column and a filter together, do it inside one `extend()` callback.

## Class-based listener

```php
<?php

declare(strict_types=1);

namespace Acme\MyPlugin\Hooks\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;

final class ExtendPostTableListener implements TableHookListenerInterface
{
    public static function getTableName(): string
    {
        return 'post.table';
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public static function extend(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(),
            TextColumn::make('seo_title')->toggleable(isToggledHiddenByDefault: true),
        ]);
    }
}
```

Drop the file under a `discovery_paths` directory, then run `php artisan mks:discover`. The class is recorded in `mks_hooks` for inventory; execution still flows through `TableHookManager::apply()` (the DB row is metadata, not the dispatch path).

## Runtime registration

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Filament\Tables\Filters\SelectFilter;

public function boot(): void
{
    Hooks::extendTableFilters('post.table', function ($table) {
        return $table->filters([
            ...$table->getFilters(),
            SelectFilter::make('locale')->options([
                'fa' => 'فارسی',
                'en' => 'English',
            ]),
        ]);
    });
}
```

## Replacing vs appending columns

Calling `$table->columns([…])`, `->filters([…])`, etc. **replaces** the previous list. Always splat the existing list first if you want to append:

```php
return $table->columns([
    ...$table->getColumns(),
    TextColumn::make('updated_at')->dateTime(),
]);
```

Same applies to actions/filters/bulk actions. Replacing without splat is sometimes intentional (a plugin that takes over the entire table), but you should call that out in the plugin’s README — it _will_ surprise other plugin authors.

## Error handling — different from forms

`FormHookManager::apply()` catches exceptions and continues with the next callback. **`TableHookManager::apply()` does not.** A throwing table hook crashes the page that renders the table. If you intercept user-supplied input or perform queries inside a hook, wrap the body in your own try/catch.

This asymmetry is a real wart. Treat it as the contract today and document loudly in your plugin if you ship a hook that may throw.

## Naming and discoverability

Table names follow the same `'<resource>.table'` convention as form names. There is no central registry; if you typo the name, the hook silently never runs. Add a smoke test that asserts your hook fires.

## See also

- [Two hook families](overview-two-families.md)
- [Form hooks](form-hooks.md)
- Reference: [`TableHookListenerInterface`](../../reference/contracts.md#tablehooklistenerinterface), [`TableHookManager`](../../reference/facades-and-managers.md#tablehookmanager)
