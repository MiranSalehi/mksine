<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Core\PageBuilder\PayloadNormalizer;
use Miran\Mksine\Models\Concerns\SanitizesHtmlOnSave;

class PageBuilderSection extends Model
{
    use SanitizesHtmlOnSave;

    protected $fillable = [
        'name',
        'builder_payload',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'builder_payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PageBuilderSection $section): void {
            $payload = is_array($section->builder_payload) ? $section->builder_payload : [];
            $ownerId = $section->exists ? (int) $section->getKey() : null;

            $section->builder_payload = app(PayloadNormalizer::class)->normalize($payload, false, $ownerId);
        });
    }

    /**
     * @return list<string>
     */
    public function sanitizedHtmlAttributes(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function sanitizedBuilderPayloadAttributes(): array
    {
        return ['builder_payload'];
    }
}
