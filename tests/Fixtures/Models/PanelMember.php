<?php

declare(strict_types=1);

namespace Miran\Mksine\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A user model with no policy anywhere, like a host whose `app/Policies` was never deployed.
 *
 * It does not extend `App\Models\User`: Laravel would hand it that model's policy.
 */
class PanelMember extends Model
{
    protected $table = 'users';
}
