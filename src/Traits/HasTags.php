<?php

declare(strict_types=1);

namespace Miran\Mksine\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Miran\Mksine\Models\Tag;

trait HasTags
{
    /**
     * Get all tags attached to this model.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
