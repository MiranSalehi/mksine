<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Immutable outcome of an update pipeline run.
 *
 * Carries enough information for the UI to render a status banner, a step-by-step
 * log, and recovery instructions if the update failed mid-flight. The result is
 * what the Filament pages and CLI commands both consume.
 */
final class UpdateResult
{
    /**
     * @param  list<string>  $steps
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly bool $success,
        public readonly UpdateTarget $target,
        public readonly string $identifier,
        public readonly ?string $fromVersion,
        public readonly ?string $toVersion,
        public readonly array $steps,
        public readonly array $warnings,
        public readonly ?string $errorMessage,
        public readonly ?int $errorPhase,
        public readonly string $logPath,
        public readonly ?string $backupPath,
        public readonly bool $dbPossiblyDirty = false,
    ) {}

    public static function success(
        UpdateTarget $target,
        string $identifier,
        ?string $fromVersion,
        string $toVersion,
        array $steps,
        array $warnings,
        string $logPath,
        ?string $backupPath,
    ): self {
        return new self(
            success: true,
            target: $target,
            identifier: $identifier,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            steps: array_values($steps),
            warnings: array_values($warnings),
            errorMessage: null,
            errorPhase: null,
            logPath: $logPath,
            backupPath: $backupPath,
        );
    }

    public static function failure(
        UpdateTarget $target,
        string $identifier,
        ?string $fromVersion,
        ?string $toVersion,
        array $steps,
        array $warnings,
        string $errorMessage,
        int $errorPhase,
        string $logPath,
        ?string $backupPath,
        bool $dbPossiblyDirty,
    ): self {
        return new self(
            success: false,
            target: $target,
            identifier: $identifier,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            steps: array_values($steps),
            warnings: array_values($warnings),
            errorMessage: $errorMessage,
            errorPhase: $errorPhase,
            logPath: $logPath,
            backupPath: $backupPath,
            dbPossiblyDirty: $dbPossiblyDirty,
        );
    }
}
