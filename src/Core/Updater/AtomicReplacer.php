<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Two-step rename swap of a target directory, with rollback helpers.
 *
 * Sequence:
 *   1) rename( target   -> backup )     ← if target exists
 *   2) rename( staging  -> target )
 *
 * Both renames must happen on the SAME filesystem, which is why
 * BackupManager places backups next to the target, and pipelines stage
 * extraction under the target's parent.
 *
 * If step (2) fails after step (1), rollback() restores the backup into the
 * target location so the admin doesn't end up with an empty target dir.
 *
 * This class is intentionally tiny; atomicity is delegated to the kernel's
 * rename() syscall and all complexity lives in the pipeline's error handling.
 */
final class AtomicReplacer
{
    private ?string $backupPath = null;

    private ?string $targetPath = null;

    /**
     * Swap staging into target, moving any existing target dir to backup first.
     *
     * @throws UpdateException
     */
    public function swap(string $stagingPath, string $targetPath, string $backupPath): void
    {
        $stagingReal = realpath($stagingPath);
        if ($stagingReal === false) {
            throw UpdateException::replace("Staging dir not found: {$stagingPath}");
        }

        $this->targetPath = $targetPath;
        $this->backupPath = $backupPath;

        if (file_exists($targetPath)) {
            if (! @rename($targetPath, $backupPath)) {
                $this->clearState();
                throw UpdateException::replace("Unable to rename target to backup: {$targetPath} -> {$backupPath}");
            }
        } else {
            // No existing target; skip the backup step but still record it as null for rollback.
            $this->backupPath = null;
        }

        if (! @rename($stagingPath, $targetPath)) {
            // Try to restore backup so we don't leave an empty target location.
            if ($this->backupPath !== null && ! @rename($this->backupPath, $targetPath)) {
                throw UpdateException::replace(
                    "Unable to move staging to target AND unable to restore backup. Target: {$targetPath}, Backup: {$this->backupPath}"
                );
            }

            throw UpdateException::replace("Unable to rename staging to target: {$stagingPath} -> {$targetPath}");
        }
    }

    /**
     * Restore backup into target (reverses swap()).
     *
     * Used when a post-swap step fails AFTER the swap succeeded and caller
     * wants to roll back to the previous version.
     *
     * @throws UpdateException
     */
    public function rollback(): void
    {
        if ($this->targetPath === null) {
            return;
        }

        if ($this->backupPath === null || ! is_dir($this->backupPath)) {
            // Nothing to roll back to.
            return;
        }

        // Move current target aside first (we may want to inspect the broken new files later).
        $abandoned = $this->targetPath . '.failed-' . date('Ymd-His');
        if (file_exists($this->targetPath) && ! @rename($this->targetPath, $abandoned)) {
            throw UpdateException::replace("Unable to move failed target aside: {$this->targetPath}");
        }

        if (! @rename($this->backupPath, $this->targetPath)) {
            throw UpdateException::replace("Unable to restore backup: {$this->backupPath} -> {$this->targetPath}");
        }
    }

    public function backupPath(): ?string
    {
        return $this->backupPath;
    }

    private function clearState(): void
    {
        $this->backupPath = null;
        $this->targetPath = null;
    }
}
