<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Closure;
use Throwable;

/**
 * Shared execution envelope for plugin and theme ZIP updates.
 */
final class UpdateRunner
{
    /**
     * @param  Closure(UpdateLog, UpdateContext): void  $work
     */
    public function run(UpdateTarget $target, string $identifier, Closure $work): UpdateResult
    {
        $log = UpdateLog::forRun($target, $identifier);
        $lock = new UpdateLock($target, $identifier);
        $ctx = new UpdateContext;

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
                steps: $ctx->steps,
                warnings: $ctx->warnings,
                errorMessage: $e->getMessage(),
                errorPhase: $e->phase(),
                logPath: $log->path(),
                backupPath: null,
                dbPossiblyDirty: false,
            );
        }

        try {
            $work($log, $ctx);

            $log->info(sprintf(
                'Update complete: %s:%s %s -> %s',
                $target->value,
                $identifier,
                $ctx->fromVersion ?? 'null',
                $ctx->toVersion ?? 'null'
            ));

            return UpdateResult::success(
                target: $target,
                identifier: $identifier,
                fromVersion: $ctx->fromVersion,
                toVersion: (string) ($ctx->toVersion ?? ''),
                steps: $ctx->steps,
                warnings: $ctx->warnings,
                logPath: $log->path(),
                backupPath: $ctx->backupPath,
            );
        } catch (UpdateException $e) {
            $log->error('Update failed (phase='.$e->phase().'): '.$e->getMessage());
            $dbDirty = $ctx->dbPossiblyDirty;
            if ($dbDirty) {
                $log->warning('DB may be partially migrated. Manual inspection required.');
            }

            return UpdateResult::failure(
                target: $target,
                identifier: $identifier,
                fromVersion: $ctx->fromVersion,
                toVersion: $ctx->toVersion,
                steps: $ctx->steps,
                warnings: $ctx->warnings,
                errorMessage: $e->getMessage(),
                errorPhase: $e->phase(),
                logPath: $log->path(),
                backupPath: $ctx->backupPath,
                dbPossiblyDirty: $dbDirty,
            );
        } catch (Throwable $e) {
            $log->error('Unexpected error: '.$e::class.': '.$e->getMessage());
            $log->error($e->getTraceAsString());

            $phase = $ctx->swapped ? UpdateException::PHASE_POST : UpdateException::PHASE_VALIDATION;

            return UpdateResult::failure(
                target: $target,
                identifier: $identifier,
                fromVersion: $ctx->fromVersion,
                toVersion: $ctx->toVersion,
                steps: $ctx->steps,
                warnings: $ctx->warnings,
                errorMessage: $e->getMessage(),
                errorPhase: $phase,
                logPath: $log->path(),
                backupPath: $ctx->backupPath,
                dbPossiblyDirty: $ctx->dbPossiblyDirty,
            );
        } finally {
            $lock->release();
            $log->info('Lock released');
        }
    }
}
