<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Execution;

use Illuminate\Database\Migrations\Migrator;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationCatalog;
use Miran\Mksine\SmartMigration\Catalog\SmartMigrationEntry;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class PendingMigrationRunner
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly SmartMigrationCatalog $catalog,
    ) {}

    /**
     * @param  list<string>  $migrationNames
     * @return list<string>
     */
    public function describe(array $migrationNames): array
    {
        return array_map(
            fn (SmartMigrationEntry $entry): string => sprintf(
                'Run pending migration [%s] %s',
                $entry->sourceLabel,
                $entry->name,
            ),
            $this->resolvePendingEntries($migrationNames),
        );
    }

    /**
     * @param  list<string>  $migrationNames
     * @return list<string>
     */
    public function run(bool $pretend, ?string $database, array $migrationNames, ?OutputInterface $output = null): array
    {
        $entries = $this->resolvePendingEntries($migrationNames);

        if ($entries === []) {
            return [];
        }

        $files = array_map(fn (SmartMigrationEntry $entry): string => $entry->path, $entries);
        $capture = $output instanceof BufferedOutput ? $output : new BufferedOutput();
        $sink = $output ?? $capture;

        $run = function () use ($files, $pretend, $sink, $capture, $output): array {
            $this->migrator->setOutput($sink);
            $this->migrator->runPending($files, ['pretend' => $pretend]);

            if ($output !== null) {
                return [];
            }

            return $this->linesFromBufferedOutput($capture);
        };

        if ($database !== null) {
            return $this->migrator->usingConnection($database, $run);
        }

        return $run();
    }

    /**
     * @param  list<string>  $migrationNames
     * @return list<SmartMigrationEntry>
     */
    private function resolvePendingEntries(array $migrationNames): array
    {
        $byName = [];

        foreach ($this->catalog->entries() as $entry) {
            $byName[$entry->name] = $entry;
        }

        $entries = [];

        foreach (array_values(array_unique($migrationNames)) as $name) {
            $entry = $byName[$name] ?? null;

            if ($entry === null || $entry->executed) {
                continue;
            }

            $entries[] = $entry;
        }

        usort($entries, fn (SmartMigrationEntry $a, SmartMigrationEntry $b): int => strcmp($a->name, $b->name));

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function linesFromBufferedOutput(BufferedOutput $output): array
    {
        $text = trim($output->fetch());

        if ($text === '') {
            return [];
        }

        return array_values(array_filter(
            explode("\n", $text),
            fn (string $line): bool => trim($line) !== '',
        ));
    }
}
