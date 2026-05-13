<?php

declare(strict_types=1);

namespace Miran\Mksine\Contracts;

/**
 * Models that can be the target of public {@see \Miran\Mksine\Models\Comment} threads
 * may implement this contract so the storefront can gate submission (e.g. per-product toggle).
 */
interface AllowsPublicComments
{
    public function allowsPublicComments(): bool;
}
