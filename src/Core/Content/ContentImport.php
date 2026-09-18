<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Support\MediaStoragePath;

/**
 * Stable create/update API for Post, Page, and Media used by import plugins.
 */
final class ContentImport
{
    public const string FILTER_ROW = 'mksine.import.row';

    /**
     * @param  array<string, mixed>  $row
     */
    public function importRow(string $type, array $row, ?int $authorId = null): ?Model
    {
        $filtered = Hooks::filter(self::FILTER_ROW, $row, $type, $authorId);
        if ($filtered === null) {
            return null;
        }

        if (! is_array($filtered)) {
            $filtered = $row;
        }

        return match ($type) {
            'post' => $this->upsertPost($filtered, $authorId),
            'page' => $this->upsertPage($filtered, $authorId),
            'media' => $this->upsertMedia($filtered, $authorId),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function upsertPost(array $row, ?int $authorId = null): Post
    {
        $slug = $this->slugFromRow($row);
        $payload = $this->postPayload($row, $authorId, $slug);
        $existing = Post::query()->withTrashed()->where('slug', $slug)->first();

        if ($existing instanceof Post) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return Post::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function upsertPage(array $row, ?int $authorId = null): Page
    {
        $slug = $this->slugFromRow($row);
        $payload = [
            'title' => $this->string($row, 'title') ?: $slug,
            'slug' => $slug,
            'content' => $this->string($row, 'content'),
            'status' => $this->status($row),
            'meta_title' => $this->nullableString($row, 'meta_title'),
            'meta_description' => $this->nullableString($row, 'meta_description'),
            'focus_keyphrase' => $this->nullableString($row, 'focus_keyphrase'),
            'published_at' => $this->publishedAt($row),
            'created_by' => $this->authorId($row, $authorId),
            'updated_by' => $this->authorId($row, $authorId),
            'type' => $this->string($row, 'type') ?: 'page',
        ];

        $existing = Page::query()->withTrashed()->where('slug', $slug)->first();
        if ($existing instanceof Page) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return Page::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function upsertMedia(array $row, ?int $authorId = null): Media
    {
        $fileName = $this->string($row, 'file_name') ?: $this->string($row, 'name') ?: 'file-'.Str::lower(Str::random(8));
        $path = $this->nullableString($row, 'path') ?: MediaStoragePath::relativePath($fileName);
        $url = $this->nullableString($row, 'url');

        $existing = Media::query()
            ->withTrashed()
            ->when($url, fn ($q) => $q->where('url', $url))
            ->when($url === null, fn ($q) => $q->where('path', $path))
            ->first();

        $payload = [
            'name' => $this->string($row, 'name') ?: pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => $this->nullableString($row, 'mime_type') ?: 'application/octet-stream',
            'size' => (int) ($row['size'] ?? 0),
            'disk' => $this->string($row, 'disk') ?: 'public',
            'path' => $path,
            'url' => $url,
            'alt' => $this->nullableString($row, 'alt'),
            'title' => $this->nullableString($row, 'title'),
            'caption' => $this->nullableString($row, 'caption'),
            'uploaded_by' => $authorId,
        ];

        if ($existing instanceof Media) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        $contents = $this->nullableString($row, 'contents');
        if ($contents !== null && $contents !== '') {
            Storage::disk($payload['disk'])->put($path, $contents);
            $payload['size'] = strlen($contents);
        }

        return Media::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function postPayload(array $row, ?int $authorId, string $slug): array
    {
        return [
            'title' => $this->string($row, 'title') ?: $slug,
            'slug' => $slug,
            'content' => $this->string($row, 'content'),
            'excerpt' => $this->nullableString($row, 'excerpt'),
            'status' => $this->status($row),
            'author_id' => $this->authorId($row, $authorId),
            'published_at' => $this->publishedAt($row),
            'meta_title' => $this->nullableString($row, 'meta_title'),
            'meta_description' => $this->nullableString($row, 'meta_description'),
            'focus_keyphrase' => $this->nullableString($row, 'focus_keyphrase'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function slugFromRow(array $row): string
    {
        $slug = Str::slug($this->string($row, 'slug'));
        if ($slug !== '') {
            return $slug;
        }

        $fromTitle = Str::slug($this->string($row, 'title'));

        return $fromTitle !== '' ? $fromTitle : 'item-'.Str::lower(Str::random(8));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function status(array $row): string
    {
        $status = strtolower($this->string($row, 'status') ?: 'draft');
        $status = match ($status) {
            'publish', 'published' => 'published',
            'inherit', 'private', 'draft' => 'draft',
            'archived', 'trash' => 'archived',
            default => in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft',
        };

        return $status;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function publishedAt(array $row): mixed
    {
        $raw = $row['published_at'] ?? $row['post_date'] ?? null;
        if ($raw === null || $raw === '') {
            return $this->status($row) === 'published' ? now() : null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function authorId(array $row, ?int $authorId): ?int
    {
        if ($authorId !== null && $authorId > 0) {
            return $authorId;
        }

        $fromRow = isset($row['author_id']) ? (int) $row['author_id'] : 0;

        return $fromRow > 0 ? $fromRow : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function string(array $row, string $key): string
    {
        $value = $row[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function nullableString(array $row, string $key): ?string
    {
        $value = $this->string($row, $key);

        return $value === '' ? null : $value;
    }
}
