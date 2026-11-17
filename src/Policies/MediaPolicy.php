<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Media;

/**
 * Default policy for {@see Media} (`ViewAny:Media`, `Update:Media`, ...).
 *
 * Used only when the app has no `App\Policies\MediaPolicy`.
 */
class MediaPolicy extends ShieldModelPolicy {}
