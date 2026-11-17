<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Page;

/**
 * Default policy for {@see Page} (`ViewAny:Page`, `Update:Page`, ...).
 *
 * Used only when the app has no `App\Policies\PagePolicy`.
 */
class PagePolicy extends ShieldModelPolicy {}
