<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

use Miran\Mksine\Models\Category;

/**
 * Default policy for {@see Category} (`ViewAny:Category`, `Update:Category`, ...).
 *
 * Used only when the app has no `App\Policies\CategoryPolicy`.
 */
class CategoryPolicy extends ShieldModelPolicy {}
