---
title: Media library
---

# Media library

The media library is a single-disk file store with a Filament resource (`Media`) and a polymorphic attachment table (`media_attachments`). It is intentionally **not** a wrapper around `spatie/laravel-medialibrary` — it’s a thinner, opinionated implementation that ships with the package.

This guide covers configuration, how plugins read and write media, and the Filament `MediaPicker` form component.

## Configuration

```php
'media' => [
    'disk'                => env('MKS_CMS_MEDIA_DISK', 'public'),
    'path'                => env('MKS_CMS_MEDIA_PATH', 'media'),
    'max_size'            => env('MKS_CMS_MEDIA_MAX_SIZE', 10240),         // KB
    'allowed_types'       => [/* mime list */],
    'optimize_images'     => env('MKS_CMS_OPTIMIZE_IMAGES', true),
    'generate_thumbnails' => env('MKS_CMS_GENERATE_THUMBNAILS', true),
    'thumbnail_sizes'     => ['small' => [150,150], 'medium' => [300,300], 'large' => [600,600]],
],
```

| Key                   | Honoured by                                                                  | Notes                                                                                                                       |
| --------------------- | ---------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `disk`                | Upload pipeline; `Media::getFullUrlAttribute()` falls back to it             | Must exist in `config/filesystems.php`. The package does not register one for you.                                          |
| `path`                | Upload pipeline                                                              | Relative path under the disk root. Files are stored under `{path}/{yyyy}/{mm}/...` (verify in your installation’s upload code). |
| `max_size`            | Upload form validation                                                       | KB, not bytes. PHP-side `upload_max_filesize` and `post_max_size` must accommodate it; the package does **not** override `php.ini`. |
| `allowed_types`       | Upload form validation                                                       | Mime type whitelist. Editors uploading anything else get a validation error.                                                |
| `optimize_images`     | Image post-processing                                                        | Requires the underlying optimizer libraries (jpegoptim, optipng, …) installed on the server. Enabling without them is a no-op or a warning depending on the optimizer. |
| `generate_thumbnails` | Image post-processing                                                        | Generates small/medium/large variants on upload. Re-running on existing media is **not** automatic.                          |
| `thumbnail_sizes`     | Thumbnail generator                                                          | Add or remove sizes; existing media retains whatever was generated at upload time.                                          |

> **Read this twice.** If you change `disk` or `path` after uploads exist, *URLs do not migrate*. The DB stores `disk` and `path` per row; old rows still resolve to the old location, new rows to the new one. Plan a manual reconciliation if you switch disks.

## Data model

Two tables:

### `media`

Columns the framework relies on:

```text
id             bigint
name           string   - editor-facing label
file_name      string   - actual file on disk
mime_type      string
size           int      - bytes
width, height  int|null - for images
disk           string
path           string   - relative to the disk root
url            string|null - if set, used verbatim via asset(); else Storage::url()
alt, title, caption  string|null
uploaded_by    fk → users(id)
deleted_at     timestamp - soft deletes
```

The `Media` model exposes:

- `isImage()`, `isVideo()`, `isDocument()` mime helpers.
- `getFullUrlAttribute()` returns the public URL, with `url` taking precedence over `Storage::disk(...)->url($path)`.
- `getHumanSizeAttribute()` for display.
- Scopes: `images()`, `videos()`, `documents()`.

### `media_attachments`

A polymorphic join table:

```text
id              bigint
media_id        fk
mediable_type   string  - morph type
mediable_id     bigint  - morph id
collection_name string  - logical bucket per parent (e.g. "gallery", "cover")
alt             string|null - per-attachment alt override
```

This is what `MediaPicker` writes when you use it in `relation` mode. There is no Eloquent relationship pre-wired on your model — you create one yourself if you want to query attachments directly.

## Authorization

`MediaResource` is gated by Filament Shield via `MediaPolicy` (registered in `MksineServiceProvider`). Roles need `media:view_any`, `:create`, `:update`, `:delete` etc.

Soft deletes are enabled. Trashed media keep their files on disk until force-deleted. The package does **not** clean up orphaned attachments when a `Media` row is hard-deleted — you should add a model observer or a periodic job if you care about referential cleanliness.

## Programmatic upload

There is no public service class for "upload a file and get a `Media` row". The Filament resource does it inside its create-form pipeline. If you need to upload from code (importer, queued job), do it manually:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Models\Media;

function storeMedia(UploadedFile $file, ?int $userId = null): Media
{
    $disk = config('mksine.media.disk');
    $path = config('mksine.media.path').'/'.date('Y/m');
    $stored = $file->store($path, $disk);

    [$w, $h] = @getimagesize($file->getRealPath()) ?: [null, null];

    return Media::create([
        'name'       => $file->getClientOriginalName(),
        'file_name'  => basename($stored),
        'mime_type'  => $file->getMimeType(),
        'size'       => $file->getSize(),
        'width'      => $w,
        'height'     => $h,
        'disk'       => $disk,
        'path'       => $stored,
        'uploaded_by'=> $userId,
    ]);
}
```

Validate `$file->getMimeType()` against `config('mksine.media.allowed_types')` first. The framework does not do this automatically when you write rows directly.

## Attaching to your model

You can either:

1. **Store a `media_id` on your model** (single-image fields): `pages.cover_media_id` style. Cheap, simple, no join table needed.
2. **Use `media_attachments`** for one-to-many or named collections (gallery, downloads, header images).

Wire a relation:

```php
class Article extends Model
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(\Miran\Mksine\Models\MediaAttachment::class, 'mediable');
    }

    public function gallery(): MorphMany
    {
        return $this->attachments()->where('collection_name', 'gallery');
    }
}
```

## `MediaPicker` field

A Filament `Field` for selecting existing media. Two modes:

```php
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

// Single image stored on the parent model column
MediaPicker::make('cover_media_id')
    ->collection('cover')
    ->acceptedFileTypes(['image/*'])
    ->relation(false);

// Multi-image gallery via media_attachments join
MediaPicker::make('gallery')
    ->multiple()
    ->collection('gallery')
    ->relation(true) // default
    ->maxItems(20);
```

Method reference (only public methods worth knowing):

| Method                        | Purpose                                                                                                |
| ----------------------------- | ------------------------------------------------------------------------------------------------------ |
| `multiple(bool $flag = true)` | Allow selecting more than one media item.                                                              |
| `collection(string $name)`    | Bucket name persisted to `media_attachments.collection_name`. Defaults to the field name.              |
| `acceptedFileTypes(array)`    | Mime patterns accepted in the picker UI (`image/*`, `application/pdf`, …).                              |
| `maxItems(int)`                | Server-side validation cap on selection count.                                                         |
| `minItems(int)`                | Server-side floor (rejects save with fewer items).                                                     |
| `authorize(Closure $fn)`      | Custom `Closure(array $ids): bool` to check whether the user may attach the selected ids.              |

Important behaviours:

- In `relation(true)` mode (the default), `MediaPicker` writes `media_attachments` rows during `saveRelationships`. The form column itself dehydrates to `null`, so don’t add a column to your model for it.
- `authorize()` runs before saving. If it returns `false`, validation fails — don’t put expensive queries in there.
- The picker does not enforce your collection’s `acceptedFileTypes` against existing media. A user can pick a video out of the library even if you set `image/*`, because the filter is only applied to *new* uploads launched from the picker. Audit on save if it matters.

## Limitations

- **Single disk per row.** A media row lives on exactly one disk. Mixed-disk setups (e.g. `local` for previews + `s3` for serving) need application-level branching on `Media::disk`.
- **No conversions API.** Thumbnail sizes are the only built-in transform. Want WebP variants on the fly? Build it in your app or use a CDN.
- **No CDN URL rewriter.** The `url` column is a hard override; if your CDN URL changes, you migrate manually.
- **No focal-point support.** Cropping is up to your front-end / CSS.
- **No usage tracking.** Deleting a `Media` row does not warn you that 17 articles still reference it. Add a soft-delete listener if you need it.
- **`MediaPicker` does not stream large libraries.** It hydrates the relation into memory before rendering. For 100k+ media libraries, build a custom picker.

## Related work and what to read next

- Reference: configuration keys are listed in [`reference/configuration.md`](../../reference/configuration.md#media).
- The Filament resource itself is unremarkable — explore `Miran\Mksine\Filament\Resources\Media\MediaResource` for the table/form structure.
- For migrating media between disks, write a one-off Artisan command. There is no first-party tool.
