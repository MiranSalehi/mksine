<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Comment;

/**
 * Default policy for {@see Comment} (`ViewAny:Comment`, `Update:Comment`, ...).
 *
 * Used only when the app has no `App\Policies\CommentPolicy`.
 */
class CommentPolicy extends ShieldModelPolicy {}
