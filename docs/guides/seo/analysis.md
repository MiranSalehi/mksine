---
title: SEO analysis
description: Advisory Yoast-like SEO scoring for Filament resources, focus keyphrase, and the mksine.seo.analysis_checks filter.
---

# SEO analysis

MKSine ships a **pure PHP** SEO / readability scorer and a Filament panel that looks like Yoast: a search snippet, an overall score with a traffic light, and two lists of pass / improve / fail items.

The score is **advisory**. It never fails validation and never rewrites copy. Builder pages often have empty HTML; body checks stay advisory (the analyzer does not walk `builder_payload`).

English may include a simple Flesch-like number labeled as English-only. **Persian and Kurdish use sentence, paragraph, and heading heuristics only** — the English Flesch formula is not valid for those languages.

## Migrate

Host after pull:

```bash
php artisan migrate
```

This adds:

- `media_attachments.sort_order` (gallery reorder — see [Media library](../media/library.md))
- nullable `focus_keyphrase` (max 191) on **posts**, **pages**, **categories**, and **tags**

`optimize:clear` is not required unless config is cached.

## Embed `SeoAnalysis` on another Filament resource

Map the live form fields, then drop the panel into the SEO section. Ecom (or any plugin) consumes this public API without forking the engine:

```php
use Miran\Mksine\Filament\Forms\Components\SeoAnalysis;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

TextInput::make('focus_keyphrase')
    ->label(__('mksine::seo.focus_keyphrase'))
    ->maxLength(191)
    ->live(debounce: 400);

TextInput::make('meta_title')->live(debounce: 400);
Textarea::make('meta_description')->live(debounce: 400);

SeoAnalysis::make('seo_analysis')
    ->titleField('name')
    ->slugField('slug')
    ->metaTitleField('meta_title')
    ->metaDescriptionField('meta_description')
    ->bodyField('description')
    ->focusKeyphraseField('focus_keyphrase');
```

Field map:

| Method | Default | Typical ecom product |
|--------|---------|----------------------|
| `titleField()` | `title` | `name` |
| `slugField()` | `slug` | `slug` |
| `metaTitleField()` | `meta_title` | `meta_title` |
| `metaDescriptionField()` | `meta_description` | `meta_description` |
| `bodyField()` | `content` | `description` |
| `focusKeyphraseField()` | `focus_keyphrase` | `focus_keyphrase` |

The analysis field is `dehydrated(false)`. Mapped inputs should be `live(debounce: 400)` (title may stay `live(onBlur)`). Body / CKEditor can keep its existing blur sync.

Core already wires this on **Post**, **Page**, **Category**, and **Tag**. Add a `focus_keyphrase` column on your own table if the resource is not one of those four.

Suggested meta length bands match the existing helpers: SEO title **50–60** characters; meta description **150–160** (the scorer treats **120–160** as the good band).

## Add checks with `Hooks::addFilter`

Hook name: `mksine.seo.analysis_checks` (constant: `SeoAnalyzer::FILTER_CHECKS`). Same pattern as `FrontendAdminBar::HOOK_ITEMS`.

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Seo\SeoAnalyzer;
use Miran\Mksine\Seo\SeoCheck;
use Miran\Mksine\Seo\SeoContext;
use Miran\Mksine\Seo\SeoFinding;

Hooks::addFilter(SeoAnalyzer::FILTER_CHECKS, function (array $checks, SeoContext $context): array {
    $checks[] = new class implements SeoCheck
    {
        public function id(): string
        {
            return 'search_boost';
        }

        public function panel(): string
        {
            return 'seo';
        }

        public function analyze(SeoContext $context): SeoFinding
        {
            return new SeoFinding(
                id: 'search_boost',
                panel: 'seo',
                status: 'ok',
                labelKey: 'ecom::seo.search_boost',
                score: 70,
            );
        }
    };

    return $checks;
});
```

Each check returns a `SeoFinding` with `status` `good` | `ok` | `bad` and `score` 0–100. The overall score is a weighted mean of findings. Empty body HTML forces a **poor** traffic light.

The engine never hits the network. Link checks parse `href` only: relative paths and `/…` count as internal; `http(s)://` counts as outbound.

See [Runtime registration](../hooks/runtime-registration.md) and [API stability](../../reference/stability.md).
