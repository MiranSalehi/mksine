---
title: Creating a page-builder block
---

# Creating a page-builder block

A "block" is a class implementing `BuilderComponentInterface` (almost always via `BaseBuilderComponent`) plus a Blade view that renders it. This guide walks through building one end-to-end from a plugin.

We'll build a `price_table` block that renders a comparison table.

## 1. The class

Place it under your plugin namespace. There is **no auto-discovery** for blocks — you'll register it manually in `boot()`.

```php
<?php

namespace Acme\Pricing\PageBuilder;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class PriceTableBlock extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'acme_price_table';
    }

    public static function getName(): string
    {
        return __('acme-pricing::builder.price_table.name');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-table-cells';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_SECTIONS;
    }

    public static function getDescription(): string
    {
        return __('acme-pricing::builder.price_table.desc');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('acme-pricing::builder.price_table.title'))
                ->maxLength(255),

            Repeater::make('plans')
                ->label(__('acme-pricing::builder.price_table.plans'))
                ->minItems(1)
                ->maxItems(4)
                ->schema([
                    TextInput::make('name')->required()->maxLength(80),
                    TextInput::make('price')->required()->maxLength(40),
                    TextInput::make('cta_label')->maxLength(40),
                    TextInput::make('cta_url')->url()->maxLength(2048),
                    Toggle::make('featured')->default(false),
                ])
                ->reorderable()
                ->collapsible(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => '',
            'plans' => [],
        ];
    }

    public static function getRenderView(): string
    {
        return 'acme-pricing::builder.price-table';
    }
}
```

### Field reference

| Method                  | Purpose                                                                                    | Notes                                                                                          |
| ----------------------- | ------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| `getType()`             | Unique string identifier stored in `builder_payload[].type`                                | **Namespace it** with your plugin id (`acme_*`) to avoid collisions. Renaming later breaks existing pages. |
| `getName()`             | Human label shown in the picker and breadcrumbs                                            | Translate.                                                                                     |
| `getIcon()`             | Heroicon name                                                                              | Use the outline set (`heroicon-o-*`) for consistency with built-ins.                            |
| `getCategory()`         | One of `CATEGORY_CONTENT`, `MEDIA`, `LAYOUT`, `INTERACTIVE`, `SECTIONS`, or a custom string | Custom categories show up grouped at the bottom of the picker.                                 |
| `getDescription()`      | Short description shown in the picker tooltip                                              | Translate.                                                                                     |
| `getSchema()`           | Filament form components used in the editor sidebar                                        | Anything Filament can render. **Don’t** depend on the parent record (`Page`) — schema is rendered standalone. |
| `getDefaultData()`      | Default `data` array when a fresh block is added                                           | Must match the keys produced by `getSchema()`. Renderers should still default missing keys.    |
| `getRenderView()`       | Blade view name for front-end rendering                                                    | Defaults to `mksine::page-builder.render.{type}`. Override to ship from your plugin namespace. |
| `supportsChildren()`    | If true, the block is a container                                                          | See [Nesting](nesting.md).                                                                     |
| `getMaxChildren()`      | Cap on direct children (or column count for `ColumnsComponent`)                            | Returns `null` for unlimited.                                                                  |
| `validate(array $data)` | Last-chance normaliser run by your code                                                    | The package **does not call this automatically**. See [Validation](validation.md).             |
| `getBuilderChildRegionLabel($i, $count)` | Label for child regions in the editor                                          | Override for containers with semantic regions ("Header", "Body").                              |

## 2. The Blade view

The view receives `$data` (the block's data array) and, if it's a container, `$children`. Always default missing keys; **never** assume the schema you defined yesterday matches the data on a page edited a year ago.

```blade
{{-- packages/acme-pricing/resources/views/builder/price-table.blade.php --}}
@php
    $title = $data['title'] ?? '';
    $plans = $data['plans'] ?? [];
@endphp

<section class="acme-price-table mb-8">
    @if ($title !== '')
        <h2 class="text-2xl font-bold text-center mb-6">{{ $title }}</h2>
    @endif

    <div class="grid gap-4 md:grid-cols-{{ max(1, min(count($plans), 4)) }}">
        @foreach ($plans as $plan)
            <article @class([
                'rounded-xl p-6 border',
                'border-zinc-200' => empty($plan['featured']),
                'border-blue-500 ring-2 ring-blue-500' => ! empty($plan['featured']),
            ])>
                <h3 class="text-lg font-semibold">{{ $plan['name'] ?? '' }}</h3>
                <p class="text-3xl font-bold my-3">{{ $plan['price'] ?? '' }}</p>

                @if (! empty($plan['cta_url']))
                    <a href="{{ $plan['cta_url'] }}"
                       class="inline-flex items-center justify-center w-full px-4 py-2 rounded-lg bg-blue-600 text-white">
                        {{ $plan['cta_label'] ?? __('acme-pricing::builder.price_table.choose') }}
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
```

If `getRenderView()` returns a non-existent view name, the renderer falls back to a yellow "Unknown component type" placeholder. **No exception is thrown.**

## 3. Register in `boot()`

```php
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;

public function boot(): void
{
    app(ComponentRegistry::class)->register(\Acme\Pricing\PageBuilder\PriceTableBlock::class);
}
```

For multiple blocks, prefer the bulk method:

```php
app(ComponentRegistry::class)->registerMany([
    \Acme\Pricing\PageBuilder\PriceTableBlock::class,
    \Acme\Pricing\PageBuilder\PlanComparisonBlock::class,
]);
```

`register()` throws `InvalidArgumentException` if the class doesn't implement the contract. Let it crash; this is a programming error, not a runtime fault.

## 4. Permissions and discovery

There is no Filament Shield permission per block. Anyone who can edit the host `Page` resource can add or remove your block. If your block exposes sensitive functionality (admin links, internal endpoints), guard the rendered output and not the editor.

There is no Artisan command to "discover" blocks. The registry is in-memory, populated every boot from `MksineServiceProvider::register()` and from your plugin's `boot()`.

## 5. What you should not do

- **Do not** read the `Page` model inside `getSchema()`. The form is rendered standalone and the parent record isn't reliably available.
- **Do not** perform DB queries inside Blade views unless cached. Builder pages can have many blocks; an N+1 here will burn the page render budget.
- **Do not** name your block with a generic key like `card`, `section`, or `grid`. Conflicts at registration time are silent if registered with a unique class but identical type — the **last** registration wins.
- **Do not** rely on `data` shape stability. Editors can ship payloads created with older schemas; defaulting in the view is mandatory.

## See also

- [Concepts and feature flag](concepts-and-feature-flag.md)
- [Nesting](nesting.md)
- [Validation](validation.md)
- [Rendering](rendering.md)
- Reference: [`BuilderComponentInterface`](../../reference/contracts.md#buildercomponentinterface), [`BaseBuilderComponent`](../../reference/contracts.md#basebuildercomponent), [`ComponentRegistry`](../../reference/facades-and-managers.md#componentregistry)
