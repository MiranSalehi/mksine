<?php

declare(strict_types=1);

namespace Miran\Mksine\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Support\Security\HtmlContentSanitizer;

/**
 * Cleans rich-text attributes on save unless a Super Admin is saving ({@see HtmlContentSanitizer}).
 *
 * Models list their HTML attributes in {@see sanitizedHtmlAttributes()} and page builder payloads
 * in {@see sanitizedBuilderPayloadAttributes()}.
 */
trait SanitizesHtmlOnSave
{
    public static function bootSanitizesHtmlOnSave(): void
    {
        static::saving(static function (Model $model): void {
            /** @var Model&self $model */
            app(HtmlContentSanitizer::class)->sanitizeModel(
                $model,
                $model->sanitizedHtmlAttributes(),
                $model->sanitizedBuilderPayloadAttributes(),
            );
        });
    }

    /**
     * @return list<string>
     */
    public function sanitizedHtmlAttributes(): array
    {
        return ['content'];
    }

    /**
     * @return list<string>
     */
    public function sanitizedBuilderPayloadAttributes(): array
    {
        return [];
    }
}
