<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

use Illuminate\Filesystem\Filesystem;

/**
 * Persists which steps already finished, so an install interrupted by a timeout can resume.
 * The file's presence also tells {@see InstallationState} that an installation is in progress.
 */
class InstallProgress
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly InstallationState $installation,
    ) {}

    public function exists(): bool
    {
        return $this->files->exists($this->path());
    }

    public function start(InstallContext $context): void
    {
        if ($this->exists()) {
            return;
        }

        $this->write([
            'started_at' => now()->toIso8601String(),
            'context' => $context->toArray(),
            'completed' => [],
            'failed' => null,
        ]);
    }

    public function isCompleted(string $stepKey): bool
    {
        return in_array($stepKey, $this->completedKeys(), true);
    }

    /**
     * @return list<string>
     */
    public function completedKeys(): array
    {
        $completed = $this->read()['completed'] ?? [];

        return is_array($completed) ? array_values(array_filter($completed, is_string(...))) : [];
    }

    /**
     * No-op when the progress file is gone (the finalize step removes it).
     */
    public function markCompleted(string $stepKey): void
    {
        if (! $this->exists()) {
            return;
        }

        $data = $this->read();
        $data['completed'] = array_values(array_unique([...$this->completedKeys(), $stepKey]));
        $data['failed'] = null;

        $this->write($data);
    }

    public function recordFailure(string $stepKey, string $error): void
    {
        if (! $this->exists()) {
            return;
        }

        $data = $this->read();
        $data['failed'] = ['step' => $stepKey, 'error' => $error, 'at' => now()->toIso8601String()];

        $this->write($data);
    }

    /**
     * @return array{step: string, error: string, at: string}|null
     */
    public function lastFailure(): ?array
    {
        $failed = $this->read()['failed'] ?? null;

        return is_array($failed) ? $failed : null;
    }

    public function clear(): void
    {
        $this->files->delete($this->path());
        $this->installation->refresh();
    }

    public function path(): string
    {
        return $this->installation->progressFilePath();
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $decoded = json_decode((string) $this->files->get($this->path()), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function write(array $data): void
    {
        $this->files->ensureDirectoryExists(dirname($this->path()));
        $this->files->put($this->path(), (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->installation->refresh();
    }
}
