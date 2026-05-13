<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use RuntimeException;
use Throwable;

/**
 * Raised by the updater subsystem when any step of an update pipeline fails.
 *
 * The pipeline differentiates failures into three phases so the caller knows
 * whether on-disk state is still intact:
 *
 *  - PHASE_VALIDATION  — ZIP rejected; target on disk untouched.
 *  - PHASE_REPLACE     — files half-swapped; AtomicReplacer must have rolled back.
 *  - PHASE_POST        — swap committed; post-steps (publish/migrate) failed. Code is
 *                        the NEW version, DB may be partially migrated. No auto-DB-rollback.
 *
 * The code carrying phase semantics lets the UI surface the right recovery
 * guidance without parsing strings.
 */
class UpdateException extends RuntimeException
{
    public const PHASE_VALIDATION = 10;

    public const PHASE_REPLACE = 20;

    public const PHASE_POST = 30;

    public function __construct(string $message, int $phase = self::PHASE_VALIDATION, ?Throwable $previous = null)
    {
        parent::__construct($message, $phase, $previous);
    }

    public function phase(): int
    {
        return $this->getCode();
    }

    public function isDbPossiblyDirty(): bool
    {
        return $this->phase() === self::PHASE_POST;
    }

    public static function validation(string $message, ?Throwable $previous = null): self
    {
        return new self($message, self::PHASE_VALIDATION, $previous);
    }

    public static function replace(string $message, ?Throwable $previous = null): self
    {
        return new self($message, self::PHASE_REPLACE, $previous);
    }

    public static function post(string $message, ?Throwable $previous = null): self
    {
        return new self($message, self::PHASE_POST, $previous);
    }
}
