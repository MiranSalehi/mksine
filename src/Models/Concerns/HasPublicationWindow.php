<?php

declare(strict_types=1);

namespace Miran\Mksine\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Public content: status `published` and a publish date that is empty or already reached.
 *
 * A post saved as published with a future `published_at` stays hidden until that moment, which
 * is how scheduling works without a cron job. Columns are qualified so the scope also works on
 * pivot queries (`$category->posts()`).
 */
trait HasPublicationWindow
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), 'published')
            ->where(function (Builder $query): void {
                $query->whereNull($this->qualifyColumn('published_at'))
                    ->orWhere($this->qualifyColumn('published_at'), '<=', now());
            });
    }

    public function isPubliclyPublished(): bool
    {
        return $this->getAttribute('status') === 'published'
            && ($this->getAttribute('published_at') === null || ! $this->getAttribute('published_at')->isFuture());
    }
}
