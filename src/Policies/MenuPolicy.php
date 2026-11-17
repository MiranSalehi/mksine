<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Menu;

/**
 * Default policy for {@see Menu} (`ViewAny:Menu`, `Update:Menu`, ...).
 *
 * Used only when the app has no `App\Policies\MenuPolicy`.
 */
class MenuPolicy extends ShieldModelPolicy {}
