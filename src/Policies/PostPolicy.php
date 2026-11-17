<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Post;

/**
 * Default policy for {@see Post} (`ViewAny:Post`, `Update:Post`, ...).
 *
 * Used only when the app has no `App\Policies\PostPolicy`.
 */
class PostPolicy extends ShieldModelPolicy {}
