<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Miran\Mksine\Support\Console\ConsoleProcessStatus;

class ConsoleProcess extends Model
{
    protected $table = 'mks_console_processes';

    protected $fillable = [
        'user_id',
        'runner',
        'command',
        'argv',
        'status',
        'pid',
        'output_path',
        'exit_code',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'argv' => 'array',
            'pid' => 'integer',
            'exit_code' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'status' => ConsoleProcessStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
