<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Closure;
use Throwable;

/**
 * Shared execution envelope for every updater.
 *
 * Centralises concerns that MUST be identical across plugin/theme/core:
 *   - max_execution_time lifted
 *   - ignore_user_abort so a closed browser tab won't corrupt a swap
 *   - exclusive lock on this target (throws if another run is in progress)
 *   - single log file per run
 *   - exception classification (validation/replace/post)
 *   - UpdateResult construction on every exit path
 *
 * Each concrete updater passes a closure that performs validation → swap →
 * post-steps. The closure receives the log + the current step accumulator.
 */
final class UpdateRunner
{
    /**
     * @param  Closure(UpdateLog, array<int,string>&, array<int,string>&): array{from: ?string, to: string, backup: ?string}  $work
     *         Returns an array with keys: from (prev version or null), to (new version), backup (path or null).
     */
    public function run(UpdateTarget $target, string $identifier, Closure $work): UpdateResult
    {
        $log = UpdateLog::forRun($target, $identifier);
        $lock = new UpdateLock($target, $identifier);

        $steps = [];
        $warnings = [];

        // Lift time + ensure we don't bail on browser close mid-swap.
        @set_time_limit(0);
        @ignore_user_abort(true);

        try {
            $lock->acquire();
            $log->info("Lock acquired for {$target->value}:{$identifier}");
        } catch (UpdateException $e) {
            $log->error($e->getMessage());

            return UpdateResult::failure(
                target: $target,
                identifier: $identifier,
                fromVersion: null,
                toVersion: null,
                steps: $steps,
                warnings: $warnings,
                errorMessage: $e->getMessage(),
                errorPhase: $e->phase(),
                logPath: $log->path(),
                backupPath: null,
                dbPossiblyDirty: false,
            );
        }

        $fromVersion = null;
        $toVersion = null;
        $backupPath = null;

        try {
            $outcome = $work($log, $steps, $warnings);
            $fromVersion = $outcome['from'] ?? null;
            $toVersion = $outcome['to'] ?? null;
            $backupPath = $outcome['backup'] ?? null;

            $log->info(sprintf(
                'Update complete: %s:%s %s -> %s',
                $target->value,
                $identifier,
                $fromVersion ?? 'null',
                $toVersion ?? 'null'
            ));

            return UpdateResult::success(
                target: $target,
                identifier: $identifier,
                fromVersion: $fromVersion,
                toVersion: (string) $toVersion,
                steps: $steps,
                warnings: $warnings,
                logPath: $log->path(),
                backupPath: $backupPath,
            );
        } catch (UpdateException $e) {
            $log->error('Update failed (phase=' . $e->phase() . '): ' . $e->getMessage());
            if ($e->isDbPossiblyDirty()) {
                $log->warning('DB may be partially migrated. Manual inspection required.');
            }

            return UpdateResult::failure(
                target: $target,
                identifier: $identifier,
                fromVersion: $fromVersion,
                toVersion: $toVersion,
                steps: $steps,
                warnings: $warnings,
                errorMessage: $e->getMessage(),
                errorPhase: $e->phase(),
                logPath: $log->path(),
                backupPath: $backupPath,
                dbPossiblyDirty: $e->isDbPossiblyDirty(),
            );
        } catch (Throwable $e) {
            $log->error('Unexpected error: ' . $e::class . ': ' . $e->getMessage());
            $log->error($e->getTraceAsString());

            return UpdateResult::failure(
                target: $target,
                identifier: $identifier,
                fromVersion: $fromVersion,
                toVersion: $toVersion,
                steps: $steps,
                warnings: $warnings,
                errorMessage: $e->getMessage(),
                errorPhase: UpdateException::PHASE_POST,
                logPath: $log->path(),
                backupPath: $backupPath,
                dbPossiblyDirty: true,
            );
        } finally {
            $lock->release();
            $log->info('Lock released');
        }
    }
}
