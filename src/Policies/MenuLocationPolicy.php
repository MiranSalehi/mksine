<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\MenuLocation;

/**
 * Default policy for {@see MenuLocation} (`ViewAny:MenuLocation`, `Update:MenuLocation`, ...).
 *
 * Used only when the app has no `App\Policies\MenuLocationPolicy`.
 */
class MenuLocationPolicy extends ShieldModelPolicy {}
