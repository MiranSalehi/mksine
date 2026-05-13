<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Per-target advisory lock backed by flock().
 *
 * Purpose: two operators must not update the same plugin/theme/core at the same
 * time. Two different targets may proceed concurrently.
 *
 * Lock file lives at storage/app/mksine-updates/locks/{target}-{id}.lock and
 * is released when the object is garbage-collected or ->release() is called.
 *
 * We deliberately use a NON-blocking acquire (LOCK_EX | LOCK_NB). If another
 * process holds the lock, we surface UpdateException::validation immediately
 * rather than stacking UI requests behind a blocking flock.
 */
final class UpdateLock
{
    /** @var resource|null */
    private $handle;

    private string $path;

    private bool $held = false;

    public function __construct(UpdateTarget $target, string $identifier)
    {
        $dir = storage_path('app/mksine-updates/locks');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $identifier) ?: 'unknown';
        $this->path = $dir . '/' . $target->value . '-' . $safeId . '.lock';
    }

    /**
     * Try to acquire the lock. Throws UpdateException if another run holds it.
     */
    public function acquire(): void
    {
        $handle = @fopen($this->path, 'c');
        if ($handle === false) {
            throw UpdateException::validation('Unable to open update lock file: ' . $this->path);
        }

        if (! @flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw UpdateException::validation('Another update is already running for this target. Try again shortly.');
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode([
            'pid' => getmypid(),
            'acquired_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ]) . "\n");
        fflush($handle);

        $this->handle = $handle;
        $this->held = true;
    }

    public function release(): void
    {
        if (! $this->held || $this->handle === null) {
            return;
        }

        @flock($this->handle, LOCK_UN);
        @fclose($this->handle);
        @unlink($this->path);

        $this->handle = null;
        $this->held = false;
    }

    public function __destruct()
    {
        $this->release();
    }
}
