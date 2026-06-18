<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Progress;

use Illuminate\Filesystem\Filesystem;

final class SmartMigrationProgressStore
{
    private const string RELATIVE_PATH = 'smart-migration-progress.json';

    public function __construct(
        private readonly Filesystem $files,
    ) {}

    public function path(): string
    {
        return storage_path('app/'.self::RELATIVE_PATH);
    }

    /**
     * @return list<string>
     */
    public function completedActionIds(): array
    {
        if (! $this->files->exists($this->path())) {
            return [];
        }

        $decoded = json_decode((string) $this->files->get($this->path()), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            $decoded['completed'] ?? [],
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        ));
    }

    public function markCompleted(string $actionId): void
    {
        $completed = $this->completedActionIds();

        if (! in_array($actionId, $completed, true)) {
            $completed[] = $actionId;
        }

        $this->files->put($this->path(), json_encode([
            'completed' => $completed,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function clear(): void
    {
        if ($this->files->exists($this->path())) {
            $this->files->delete($this->path());
        }
    }
}
