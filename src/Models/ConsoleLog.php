<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsoleLog extends Model
{
    protected $table = 'mks_console_logs';

    protected $fillable = [
        'user_id',
        'runner',
        'command',
        'argv',
        'output',
        'exit_code',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'argv' => 'array',
            'exit_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
