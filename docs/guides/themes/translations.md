---
title: Theme translations
---

# Theme translations

Themes ship Laravel translation files alongside their templates. They live under the theme directory and are published into the project’s `lang/vendor/theme-{identifier}/` namespace at theme activation time (or via an explicit Artisan command).

## Where they live in the theme

`mks:make-theme` creates this layout:

```
resources/views/themes/stellar/
└─ resources/lang/
   └─ en/
      └─ theme.php
```

`ThemeManager::getThemeTranslationsPath()` accepts either of these two locations:

1. `{themePath}/resources/lang/` (preferred; matches the scaffold)
2. `{themePath}/lang/` (fallback)

If neither exists, no translations are published.

## Publishing

Translations are copied to `lang/vendor/theme-{identifier}/`:

```bash
php artisan mks:theme-publish-lang stellar
# or, for every discovered theme:
php artisan mks:theme-publish-lang
```

The copy is **always overwriting**. There is no `--force` flag because there is no “safe” mode — running the command always replaces the destination. Operators should keep their _project_ overrides somewhere outside `lang/vendor/`.

`ThemeManager::activate($identifier)` also calls `publishThemeTranslations()` automatically (when `lang_path()` is available). So switching the theme in the admin re-syncs translations as a side effect.

## Referencing strings

Use the `theme-{identifier}::` namespace prefix:

```blade
{{ __('theme-stellar::theme.welcome') }}
```

Override per project by editing the published file:

```
lang/en/vendor/theme-stellar/theme.php
```

Wait — the published path is `lang/vendor/theme-stellar/`, but Laravel’s lookup order for vendor namespaces is `lang/vendor/{ns}/{locale}/...` and `lang/{locale}/vendor/{ns}/...` for project overrides. **Do not edit `lang/vendor/theme-stellar/` directly** — the next `mks:theme-publish-lang` will overwrite your edits. Override under `lang/{locale}/vendor/theme-stellar/` instead.

## Per-locale folders

Inside `resources/lang/`, create one folder per locale you ship:

```
resources/lang/
├─ en/
│  ├─ theme.php
│  └─ menu.php
├─ fa/
│  ├─ theme.php
│  └─ menu.php
└─ ar/
   └─ theme.php
```

The publish command copies the whole tree. Locales the host doesn’t use cost nothing (they sit in `lang/vendor/theme-stellar/{locale}/`).

## Recommended structure

For maintainability, group strings by purpose, not by template:

```
resources/lang/en/
├─ theme.php          # Welcome banner, footer, CTA buttons
├─ post.php           # Single post / list view labels
├─ menu.php           # Header / footer nav labels
└─ comments.php       # Comment form labels
```

Keep keys flat and use the file name as the namespace partition. Keys like `theme-stellar::post.read_more` are easier to grep than nested arrays.

## Plurals and parameters

Standard Laravel rules apply:

```php
// resources/lang/en/post.php
return [
    'read_more'      => 'Read more →',
    'comments_count' => '{0} No comments|{1} :count comment|[2,*] :count comments',
];
```

```blade
{{ trans_choice('theme-stellar::post.comments_count', $count, ['count' => $count]) }}
```

## Honest limits

- **No discovery beyond `mks:theme-publish-lang`.** Adding a new translation file mid-development requires re-running the command. The published copy is what Laravel reads.
- **No locale fallback per theme.** If a string is missing for `fa`, Laravel falls back to your global `app.fallback_locale`, not to `en` from the theme.
- **No plugin–theme inheritance.** A plugin that wants to localise a theme’s string has to override under the project’s `lang/{locale}/vendor/theme-{id}/`. There is no “theme child” concept.

## See also

- [Creating a theme](creating-a-theme.md)
- [Translations workflow](../localization/translations.md) — global localization workflow
- Reference: [`mks:theme-publish-lang`](../../reference/commands.md#themes)
