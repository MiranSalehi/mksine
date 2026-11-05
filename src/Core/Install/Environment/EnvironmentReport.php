<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Environment;

use Miran\Mksine\Core\Install\InstallProfile;

/**
 * What the server allows. Drives the recommended install profile and fallbacks.
 */
final readonly class EnvironmentReport
{
    public function __construct(
        public bool $canExec,
        public bool $canProcOpen,
        public bool $canSymlink,
        public int $maxExecutionTime,
        public string $memoryLimit,
        public ?string $documentRoot,
        public string $publicPath,
        public ?bool $publicPathIsDocumentRoot,
    ) {}

    /**
     * Shared hosting when processes cannot be spawned or the web root is not the project's public/.
     */
    public function recommendedProfile(): InstallProfile
    {
        if (! $this->canProcOpen || $this->publicPathIsDocumentRoot === false) {
            return InstallProfile::Shared;
        }

        return InstallProfile::Vps;
    }

    public function hasExecutionTimeLimit(): bool
    {
        return $this->maxExecutionTime > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'can_exec' => $this->canExec,
            'can_proc_open' => $this->canProcOpen,
            'can_symlink' => $this->canSymlink,
            'max_execution_time' => $this->maxExecutionTime,
            'memory_limit' => $this->memoryLimit,
            'document_root' => $this->documentRoot,
            'public_path' => $this->publicPath,
            'public_path_is_document_root' => $this->publicPathIsDocumentRoot,
            'recommended_profile' => $this->recommendedProfile()->value,
        ];
    }
}
