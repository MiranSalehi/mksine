<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Entry;

/**
 * Default policy for {@see Entry} (`ViewAny:Entry`, `Update:Entry`, ...).
 *
 * Used only when the app has no `App\Policies\EntryPolicy`.
 */
class EntryPolicy extends ShieldModelPolicy {}
