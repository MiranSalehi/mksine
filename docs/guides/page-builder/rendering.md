---
title: Rendering builder payloads
---

# Rendering builder payloads

This page covers how `builder_payload` is turned into HTML, where to hook your own theme into that pipeline, and the failure modes you should design for.

## The dispatcher view

A builder page is rendered by walking `builder_payload` and including the per-block view via the package’s dispatcher partial:

```1:21:packages/mksine/resources/views/page-builder/render/block.blade.php
@php
    $type = $block['type'] ?? 'unknown';
    $data = $block['data'] ?? [];
    $children = $block['children'] ?? null;

    $registry = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class);
    $viewName = $registry->resolveRenderView($type);

    $includeData = ['data' => $data];
    if (array_key_exists('children', $block)) {
        $includeData['children'] = $children;
    }
@endphp
@if ($viewName !== '' && \Illuminate\Support\Facades\View::exists($viewName))
    @include($viewName, $includeData)
@else
    {{-- Unknown component or missing view --}}
    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-800 dark:text-yellow-200 text-sm">
        {{ __('mksine::page_builder.unknown_component_type') }} {{ $type }}
    </div>
@endif
```

This is the single entry point. **Always include this partial when rendering child blocks** — never reach into a sibling block’s view directly.

The variables a block view receives:

- `$data` — the block’s `data` array. Default missing keys with `??`.
- `$children` — only present if the original block had a `children` key (containers).

## How `resolveRenderView()` picks a view

```182:191:packages/mksine/src/Core/PageBuilder/ComponentRegistry.php
    public function resolveRenderView(string $type): string
    {
        $class = $this->get($type);

        if ($class !== null && is_subclass_of($class, BaseBuilderComponent::class)) {
            return $class::getRenderView();
        }

        return 'mksine::page-builder.render.'.$type;
    }
```

Resolution order:

1. If the type is registered and the class extends `BaseBuilderComponent`, use whatever `getRenderView()` returns. This is how plugin-shipped views work — return your own namespace, e.g. `acme-pricing::builder.price-table`.
2. If not, fall back to the package convention `mksine::page-builder.render.{type}`.
3. If the resulting view does not exist, the dispatcher renders the yellow placeholder.

**No exception is ever thrown.** A misconfigured registration is silent on the front-end.

## The page rendering path

Pages are rendered by a theme template. The MKSine default theme (`themes/mksine/page.blade.php`) shows the canonical pattern:

```38:44:packages/mksine/resources/views/themes/mksine/page.blade.php
            @if($isBuilder)
                {{-- Builder pages: optional full-width (landing) --}}
                <div class="builder-content space-y-0">
                    @foreach($page->builder_payload as $block)
                        @include('mksine::page-builder.render.block', ['block' => $block])
                    @endforeach
                </div>
```

If you ship a theme, copy this pattern into your `page.blade.php`:

```blade
@php
    $isBuilder = $page->usesBuilder() && ! empty($page->builder_payload);
@endphp

@if ($isBuilder)
    <div class="my-theme-builder">
        @foreach ($page->builder_payload as $block)
            @include('mksine::page-builder.render.block', ['block' => $block])
        @endforeach
    </div>
@else
    <div class="prose">{!! $page->content !!}</div>
@endif
```

The `Page` model still carries `show_page_header` and `builder_content_width` for themes that read them (defaults: header on, `contained` width). Those columns are **not** editable in Filament anymore — adjust behaviour in your theme template or via database/programmatic updates if you need different defaults.

## Live render only

There is **no** separate admin “preview” window. Blocks render through the same dispatcher as the public page (`mksine::page-builder.render.block` inside your theme). Validate layout by opening the page on the storefront (or a local route that uses the theme layout).

If a block looks wrong on the site but seemed fine while editing, the difference is almost always:

- A theme wrapper class (`.prose`) restyling your block.
- Missing CSS/JS — the live page pulls theme assets. Enqueue scripts via `theme_enqueue_*` when needed.
- Theme actions like `page.before_content` only fire on the live render, not inside the Filament field.

## Hooking renders

The dispatcher does not fire any hooks. There is no per-block "before render" event. If you need to wrap or filter, options are:

- **Theme actions** around the loop (e.g. `@themeDoAction('page.before_content')`). Coarse-grained.
- **Wrap your own block** in your render view with whatever you need; you control the markup.
- **Override another package’s block view** by registering a class with the same `type` after the original. Last-write-wins in the registry. Use sparingly — it’s a sharp tool.

If you genuinely need a "filter every block on render" extension point, open an ADR; this is a public contract change.

## Failure modes

| Symptom                                   | Cause                                                                                            | Fix                                                                                               |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------- |
| Yellow "Unknown component type" box       | Block type not registered, or `getRenderView()` points at a nonexistent view                     | Confirm the plugin is active; clear view cache (`php artisan view:clear`); check namespace        |
| Block renders blank                       | `data` keys missing, view doesn’t default                                                        | Add `?? default` everywhere; harden `validate()`                                                  |
| Children render with the wrong layout     | Container shape mismatch (you used `children: []` instead of column buckets, or vice versa)      | Re-check `createInstance()` and your render view’s expectation                                    |
| Front-end JS missing                      | Theme didn’t load the package assets your block depends on                                       | Enqueue via `theme_enqueue_script` from `boot()`                                                  |
| Container’s child blocks don’t appear     | You forgot `@include('mksine::page-builder.render.block', …)` and tried to render directly       | Use the dispatcher                                                                                |
| 500 error on first render after deploy    | View cache holds an old compiled version; class name changed                                     | `php artisan view:clear`; redeploy                                                                |

## Performance notes

- Each block triggers a `View::exists()` check followed by a Blade compile/include. Pages with hundreds of blocks pay for this. Consider opcache + `view:cache` in production.
- The registry is queried per block (`app(ComponentRegistry::class)`). The container resolves a singleton, so this is cheap, but **do not** call DB inside `resolveRenderView()` or `getRenderView()` — both run on every block on every render.
- If a block needs heavy data (e.g. featured posts), cache it inside the block’s view with `Cache::remember()` keyed by stable inputs from `$data`. The framework offers no caching layer for blocks.

## See also

- [Concepts and feature flag](concepts-and-feature-flag.md)
- [Creating a block](creating-a-block.md)
- [Nesting](nesting.md)
- [Validation](validation.md)
- Reference: [`ComponentRegistry`](../../reference/facades-and-managers.md#componentregistry)
