<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Tag;

/**
 * Default policy for {@see Tag} (`ViewAny:Tag`, `Update:Tag`, ...).
 *
 * Used only when the app has no `App\Policies\TagPolicy`.
 */
class TagPolicy extends ShieldModelPolicy {}
