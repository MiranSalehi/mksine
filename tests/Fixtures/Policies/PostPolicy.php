<?php

declare(strict_types=1);

namespace Miran\Mksine\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Stands in for an app's own `App\Policies\PostPolicy` in the policy registrar tests.
 */
class PostPolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return true;
    }
}
