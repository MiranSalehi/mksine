---
title: Translations workflow
---

# Translations workflow

MKSine ships an admin **Languages** page (Filament page at `Miran\Mksine\Filament\Pages\Languages`) that lets editors browse and edit translation files for the host application, every active plugin, and every discovered theme — without touching the filesystem manually. This guide explains how the workflow is wired and what to do (and not do) as a developer.

## Three layers of translations

### 1. Application

`lang/{locale}/{file}.php` and `lang/{locale}.json` under the host application.

Editable from the admin via `TranslationFileManager`. Saved files are written **in place** — the same files your VCS tracks. Beware: a non-developer editing through the admin commits no SCM history, no PR, no review.

### 2. Plugin

`{plugin_root}/{plugin_id}/resources/lang/{locale}/{file}.php`. Plugins ship their PHP translation files in their own tree; the package registers them as a translation namespace `{plugin_id}::{file}.{key}`.

When the admin saves a plugin translation, two writes happen (see `AdminTranslationManager::setTranslations`):

1. The **source** file in the plugin tree is updated (this is the canonical copy).
2. The same file is **copied** to `lang/vendor/{plugin_id}/{locale}/{file}.php`.

The vendor copy is what Laravel’s translator picks up for the namespace at runtime — without it, your edits would not be visible until the plugin republishes assets.

### 3. Theme

`themes/{theme_id}/resources/lang/{locale}/{file}.php`. Same dual-write pattern: source + `lang/vendor/theme-{theme_id}/{locale}/{file}.php`.

## File key conventions

The file key (e.g. `posts`, `messages`) maps to a PHP file. The full key is `posts.title`, referenced from code as:

```php
__('mksine::posts.title');           // package
__('acme-shop::cart.empty');         // plugin (the namespace = plugin id)
__('messages.welcome');              // app
```

JSON translations (`lang/en.json`) are app-only in this admin page. Plugins and themes only have PHP files, addressed by `fileKey` (filename without `.php`).

`AdminTranslationManager::assertValidFileKey()` accepts only `[a-zA-Z0-9_-]` plus the literal string `'json'`. Anything else throws `InvalidArgumentException`.

## What the Languages admin actually does

Per locale + source the page:

1. Builds a list of editable `*.php` files from `pluginLocaleDirCandidates()` or `themeLocaleDirCandidates()` — both the source dir and the published vendor dir, **deduplicated by filename, source winning**.
2. Reads the active file’s flat key→string map.
3. On save, writes the source file, then copies it to `lang/vendor/...`.

This means **plugin/theme files stay editable from the admin even after `mks-plugin:publish-translations` has run**. The admin always edits the source first.

If a plugin’s `translationsPath()` returns `null` (the plugin doesn’t ship translations), the admin will not show write candidates for that plugin even if a `lang/vendor/{plugin_id}/...` directory exists. Editing is always through the source path.

## What plugin authors should do

1. **Ship translations under `resources/lang/{locale}/{file}.php`** in your plugin tree.
2. Implement `translationsPath()` on your plugin class to return the absolute path to that `lang` directory. Returning `null` opts out of admin editing.
3. Use stable file names. Renaming `messages.php` to `strings.php` between releases breaks any custom strings the user wrote in the admin (the new file is empty; the old one is orphaned in `lang/vendor/{id}/`).
4. Keep your namespace = plugin id. `__('my-plugin::messages.welcome')` only works if `translationsPath()` is wired and Laravel’s translator has loaded the namespace at boot.

## What theme authors should do

1. Ship translations under `themes/{id}/resources/lang/{locale}/{file}.php`.
2. The package automatically discovers them via `ThemeManager::getThemeTranslationsPath()`.
3. Run `php artisan mks:theme-publish-lang {theme_id}` to copy translations into `lang/vendor/theme-{id}/` so Laravel registers the namespace at runtime. This is also done lazily by the admin Languages page on first save.
4. Reference strings as `__('theme-{id}::file.key')` — note the `theme-` prefix.

## What host application maintainers should know

- The admin Languages page **modifies your VCS-tracked `lang/` directory** for app translations. Run a `git diff` after editors save anything; commit or revert as appropriate.
- For plugins/themes installed via Composer (path repository), the source files live under `vendor/...` and edits by an admin will be **wiped out by the next `composer install`**. Use the published vendor copies as the persistent store, or fork the plugin into your monorepo.
- File locks are not used. Two editors saving the same file simultaneously: last-write-wins, no merge.

## Common pitfalls

- **Adding a new locale.** Use `TranslationFileManager::addLocale('fr', copyFrom: 'en')` from a one-off command or seeder. There is no "Add language" button on the page.
- **Removing a locale.** `removeLocale('fr')` deletes the directory and the JSON file. Irreversible — back up first.
- **Strings with dots in keys.** Stored as nested arrays. The admin flattens them (`a.b.c => ...`). If you store a key like `a.b` that already has a child `a.b.c`, the writer will collapse one of them. Don’t mix flat and nested keys for the same prefix.
- **Locale code regex.** `^[a-z]{2}(_[A-Za-z0-9]+)?$` — `en`, `en_US`, `pt_BR` are valid. `en-US` (hyphen) is **not**. Set your locales accordingly.

## Programmatic editing

```php
use Miran\Mksine\Core\Translation\TranslationFileManager;
use Miran\Mksine\Core\Translation\AdminTranslationManager;

// App-level edit:
app(TranslationFileManager::class)->setTranslations('en', 'messages', [
    'welcome' => 'Hi',
    'logout'  => 'Sign out',
]);

// Plugin-level edit (writes plugin source + republishes vendor copy):
app(AdminTranslationManager::class)->setTranslations(
    'en',
    source:  'plugin:acme-shop',
    fileKey: 'cart',
    translations: ['empty' => 'Your cart is empty'],
);
```

`AdminTranslationManager::setTranslations` throws:

- `InvalidArgumentException` if `fileKey` is invalid or the write path can’t be resolved (e.g. plugin missing `translationsPath()`).
- `RuntimeException` if the vendor copy fails (disk full, permission denied).

## Honest limitations

- **No translation memory.** Edits don’t propagate to similar strings in other files.
- **No fallback resolution UI.** The admin shows whatever file you’re editing; it doesn’t tell you "this key falls back to `en` because `fa.messages.welcome` is missing".
- **No string extraction.** New `__()` calls in code don’t magically appear as keys to translate — you add them to the source files first.
- **JSON files are app-only.** Plugins and themes can’t use JSON translations through this admin.
- **No protection against misuse.** An editor with admin access can rewrite every translation. Audit access via Filament Shield.
- **No re-publish-after-deploy hook.** If you redeploy a plugin and want freshly-shipped translations to overwrite admin-edited vendor copies, you must do that explicitly (delete `lang/vendor/{plugin_id}/` and re-run `mks-plugin:publish-translations`).

## See also

- Plugin: [Translations](../plugins/translations.md)
- Theme: [Translations](../themes/translations.md)
- Reference: configuration auth keys live in [`reference/configuration.md`](../../reference/configuration.md).
