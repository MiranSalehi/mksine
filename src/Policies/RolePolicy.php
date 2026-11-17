<?php

declare(strict_types=1);

namespace Miran\Mksine\Policies;

/**
 * Default policy for Shield's role resource (`ViewAny:Role`, `Update:Role`, ...).
 *
 * Used only when the app has no `App\Policies\RolePolicy`.
 */
class RolePolicy extends ShieldModelPolicy {}
