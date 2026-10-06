<?php

declare(strict_types=1);

namespace Miran\Mksine\Contracts;

/**
 * Models that can be the target of public {@see \Miran\Mksine\Models\Comment} threads
 * must implement this contract *and* be listed in {@see config('mksine.commentable_types')}.
 * The storefront fails closed: a model that is only an Eloquent class is not commentable.
 */
interface AllowsPublicComments
{
    public function allowsPublicComments(): bool;
}
