<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\GeoState;

/**
 * Default policy for {@see GeoState} (`ViewAny:GeoState`, `Update:GeoState`, ...).
 *
 * Used only when the app has no `App\Policies\GeoStatePolicy`.
 */
class GeoStatePolicy extends ShieldModelPolicy {}
