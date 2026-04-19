---
title: Validation
---

# Validation

The page builder has two layers of validation. Both are weaker than what you’re probably used to from form requests. Read this page before you ship a block that depends on data integrity.

## Layer 1: Filament form rules (in the editor)

The schema returned by `getSchema()` is rendered with Filament. Whatever Filament rules you attach run when the editor saves a block:

```php
public static function getSchema(): array
{
    return [
        TextInput::make('cta_url')
            ->required()
            ->url()
            ->maxLength(2048),

        TextInput::make('price')
            ->required()
            ->numeric()
            ->minValue(0),
    ];
}
```

These rules:

- Run only **inside the editor**. Saving via the admin enforces them.
- Do **not** run when `builder_payload` is set programmatically (seeders, imports, REST endpoints, queue jobs, factory blueprints).
- Do **not** run when an existing payload is read on the front-end.

If your block stores user-influenced URLs, IDs, or HTML, do not assume `getSchema()` rules ran on the data you’re reading.

## Layer 2: `validate(array $data): array`

Each block class implements `BuilderComponentInterface::validate()`. Override it to normalise the data array — clamp enums, strip unknown keys, default missing fields. Example from the package:

```109:121:packages/mksine/src/Core/PageBuilder/Components/ContainerInsetComponent.php
    public static function validate(array $data): array
    {
        $allowedPadding = ['none', 'xs', 'sm', 'md', 'lg', 'xl', '2xl'];
        $p = $data['padding_inline'] ?? 'md';
        $data['padding_inline'] = in_array($p, $allowedPadding, true) ? $p : 'md';

        $allowedMax = ['full', 'prose', '3xl', '5xl', '6xl', '7xl'];
        $m = $data['max_width'] ?? 'full';
        $data['max_width'] = in_array($m, $allowedMax, true) ? $m : 'full';

        return $data;
    }
```

Critical fact: **the package never invokes `validate()` automatically.** It’s a contract for plugin authors to call themselves. The registry exposes a wrapper:

```168:177:packages/mksine/src/Core/PageBuilder/ComponentRegistry.php
    public function validateComponent(string $type, array $data): array
    {
        $class = $this->get($type);

        if (! $class) {
            return $data;
        }

        return $class::validate($data);
    }
```

But there is **no place in the framework that calls `validateComponent()`**. If you want it run, you must wire it up.

## Where to actually validate

For real safety, validate at the boundaries:

### 1. Before saving the page

Hook into the `Page` resource’s save flow (model events, observer, or Filament’s `mutateFormDataBeforeSave`):

```php
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;

protected function mutateFormDataBeforeSave(array $data): array
{
    $registry = app(ComponentRegistry::class);
    $data['builder_payload'] = $this->normaliseBlocks($registry, $data['builder_payload'] ?? []);
    return $data;
}

private function normaliseBlocks(ComponentRegistry $registry, array $blocks): array
{
    foreach ($blocks as &$block) {
        $type = $block['type'] ?? null;
        if ($type === null) {
            continue;
        }

        $block['data'] = $registry->validateComponent($type, $block['data'] ?? []);

        if (! empty($block['children'])) {
            foreach ($block['children'] as &$bucket) {
                if (isset($bucket['items']) && is_array($bucket['items'])) {
                    $bucket['items'] = $this->normaliseBlocks($registry, $bucket['items']);
                }
            }
        }
    }
    return $blocks;
}
```

This walks both nesting shapes (column buckets and direct children).

### 2. Before importing or programmatic writes

Anywhere you set `Page::builder_payload` from code (seeders, importers, sync jobs), call `validateComponent()` per block. Don’t trust upstream data, ever.

### 3. In the render view

Always default missing keys with `??`. Treat `validate()` as a "best-effort hardening", not a guarantee. The view runs in production with whatever shape happens to be on disk.

## What `validate()` should do

Good rules of thumb:

- **Whitelist enums.** If `level` must be `h1..h6`, fall back to a known default — never echo the raw string into your view as an HTML tag without checking.
- **Cast types.** JSON deserialisation can leave you with strings where ints were expected (especially after manual edits or imports).
- **Clamp ranges.** A `repeats` integer should not be 10,000.
- **Strip unknown keys** if your render view depends on an exact shape.

What it should **not** do:

- Throw exceptions. The render path swallows nothing — your block will crash the entire page render. Return safe defaults instead.
- Touch the database. `validate()` is called inside form-save flows and may run dozens of times per save; a DB query per block is a perf trap.
- Mutate state outside the returned array. It must be pure.

## Cross-block invariants

The contract is per-block. If your blocks have invariants that span the page (e.g. "only one `acme_hero` per page", "first block must be a heading"), you have to enforce them yourself in the resource’s save flow. The package will not stop you from creating ten heroes.

## Honest limitations

- Editor-side rules and `validate()` are not unified. A field can pass Filament’s rules and still get rewritten by `validate()` to a default — silently. Decide: either rely on Filament’s rules in the editor *and* re-check on save, or move all checks into `validate()` and keep the editor schema permissive. Don’t straddle.
- There is no schema versioning. If you change `getDefaultData()` keys in v2, payloads written by v1 will have stale shapes. `validate()` is your only hook to migrate them on read.
- There is no global "page-level validation hook." If you need one, fire it from your save observer.

## See also

- [Concepts and feature flag](concepts-and-feature-flag.md)
- [Creating a block](creating-a-block.md)
- [Nesting](nesting.md)
- [Rendering](rendering.md)
- Reference: [`BuilderComponentInterface`](../../reference/contracts.md#buildercomponentinterface), [`ComponentRegistry`](../../reference/facades-and-managers.md#componentregistry)
