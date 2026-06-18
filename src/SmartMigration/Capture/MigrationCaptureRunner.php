<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Throwable;

final class MigrationCaptureRunner
{
    private const string CAPTURE_CONNECTION = 'migrate_smart_capture';

    public function __construct(
        private readonly Migrator $migrator,
        private readonly ConnectionResolverInterface $resolver,
        private readonly BlueprintSchemaProcessor $processor,
        private readonly Filesystem $files,
    ) {}

    /**
     * @param  list<string>  $paths
     * @param  list<string>|null  $onlyMigrationNames
     * @return array{state: ExpectedSchemaState, warnings: list<string>}
     */
    public function buildExpectedState(array $paths, ?string $database = null, ?array $onlyMigrationNames = null): array
    {
        $source = $this->resolver->connection($database);
        CaptureConnection::registerResolver($source);

        Config::set('database.connections.'.self::CAPTURE_CONNECTION, array_merge(
            $source->getConfig(),
            ['driver' => 'migrate_smart_capture'],
        ));

        $this->resolver->purge(self::CAPTURE_CONNECTION);

        /** @var CaptureConnection $capture */
        $capture = $this->resolver->connection(self::CAPTURE_CONNECTION);

        $files = $this->migrator->getMigrationFiles($paths);
        $ran = $this->migrator->getRepository()->getRan();

        $state = new ExpectedSchemaState;
        $warnings = [];

        $previousDefault = $this->resolver->getDefaultConnection();

        try {
            $this->resolver->setDefaultConnection(self::CAPTURE_CONNECTION);

            foreach ($ran as $migrationName) {
                if ($onlyMigrationNames !== null && ! in_array($migrationName, $onlyMigrationNames, true)) {
                    continue;
                }

                if (! isset($files[$migrationName])) {
                    continue;
                }

                $capture->getSchemaBuilder()->flushCapturedBlueprints();
                $capture->flushRawStatements();

                try {
                    $migration = $this->resolveMigration($files[$migrationName]);

                    if (method_exists($migration, 'up')) {
                        $migration->up();
                    }
                } catch (Throwable $exception) {
                    $warnings[] = "[WARNING] Failed to capture migration [{$migrationName}]: {$exception->getMessage()}";

                    continue;
                }

                foreach ($capture->rawStatements() as $statement) {
                    if (trim($statement) !== '') {
                        $warnings[] = '[WARNING] Unsupported migration operation detected: DB::statement(...). Manual review required.';
                    }
                }

                foreach ($capture->getSchemaBuilder()->capturedBlueprints() as $blueprint) {
                    $warnings = array_merge($warnings, $this->processor->apply($state, $blueprint));
                }
            }
        } finally {
            $this->resolver->setDefaultConnection($previousDefault);
            $this->resolver->purge(self::CAPTURE_CONNECTION);
        }

        return [
            'state' => $state,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function resolveMigration(string $path): object
    {
        $migration = $this->files->getRequire($path);

        if (! is_object($migration)) {
            throw new \RuntimeException("Migration [{$path}] did not return an object instance.");
        }

        return $migration;
    }
}
