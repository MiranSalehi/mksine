<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Models\Entry;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderSection;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;
use Miran\Mksine\Support\Security\HtmlContentSanitizer;

/**
 * Lists stored content that contains script (stored XSS candidates), optionally cleaning it.
 *
 * Content saved before 1.24.0, or by anyone while panel permissions were open, was never
 * sanitised. Super Admins may have embedded scripts on purpose, so this reports first and only
 * rewrites with --fix after confirmation.
 */
class ContentScanCommand extends Command
{
    protected $signature = 'mksine:content-scan
                            {--fix : Sanitise the flagged records (Super Admin embeds are removed too)}';

    protected $description = 'Find stored posts, pages, entries, tags and builder sections that contain script';

    /** Markup that can run script in a visitor's browser. */
    public const DANGEROUS_PATTERN = '/<\s*script\b|<\s*(object|embed|base|meta|form)\b|\son[a-z]+\s*=|javascript\s*:|vbscript\s*:|srcdoc\s*=|data\s*:\s*text\/html/i';

    public function handle(HtmlContentSanitizer $sanitizer): int
    {
        $targets = [
            [Post::class, ['content'], []],
            [Page::class, ['content'], ['builder_payload']],
            [Entry::class, ['content'], []],
            [Tag::class, ['description'], []],
            [PageBuilderSection::class, [], ['builder_payload']],
        ];

        $rows = [];
        $flagged = [];

        foreach ($targets as [$modelClass, $htmlAttributes, $payloadAttributes]) {
            /** @var class-string<Model> $modelClass */
            $query = $modelClass::query();

            if (method_exists($modelClass, 'bootSoftDeletes')) {
                $query->withTrashed();
            }

            $query->chunkById(200, function ($models) use ($htmlAttributes, $payloadAttributes, &$rows, &$flagged): void {
                foreach ($models as $model) {
                    $hits = $this->dangerousAttributes($model, $htmlAttributes, $payloadAttributes);

                    if ($hits === []) {
                        continue;
                    }

                    $rows[] = [class_basename($model), $model->getKey(), (string) ($model->getAttribute('title') ?? $model->getAttribute('name') ?? ''), implode(', ', $hits)];
                    $flagged[] = [$model, $htmlAttributes, $payloadAttributes];
                }
            });
        }

        if ($rows === []) {
            $this->components->info('No stored content contains script.');

            return self::SUCCESS;
        }

        $this->table(['Model', 'ID', 'Title', 'Fields'], $rows);
        $this->components->warn(count($rows) . ' record(s) contain markup that can run script. Review them; Super Admin embeds may be intentional.');

        if (! $this->option('fix')) {
            return self::FAILURE;
        }

        if (! $this->confirm('Sanitise these records now? Embeds and scripts in them will be removed.', false)) {
            return self::FAILURE;
        }

        foreach ($flagged as [$model, $htmlAttributes, $payloadAttributes]) {
            foreach ($htmlAttributes as $attribute) {
                $value = $model->getAttribute($attribute);
                if (is_string($value)) {
                    $model->setAttribute($attribute, $sanitizer->sanitize($value));
                }
            }

            foreach ($payloadAttributes as $attribute) {
                $value = $model->getAttribute($attribute);
                if (is_array($value)) {
                    $model->setAttribute($attribute, $sanitizer->sanitizeBuilderPayload($value));
                }
            }

            $model->saveQuietly();
        }

        $this->components->info(count($flagged) . ' record(s) sanitised.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $htmlAttributes
     * @param  list<string>  $payloadAttributes
     * @return list<string>
     */
    private function dangerousAttributes(Model $model, array $htmlAttributes, array $payloadAttributes): array
    {
        $hits = [];

        foreach ($htmlAttributes as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_string($value) && preg_match(self::DANGEROUS_PATTERN, $value) === 1) {
                $hits[] = $attribute;
            }
        }

        foreach ($payloadAttributes as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_array($value) && preg_match(self::DANGEROUS_PATTERN, (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === 1) {
                $hits[] = $attribute;
            }
        }

        return $hits;
    }
}
