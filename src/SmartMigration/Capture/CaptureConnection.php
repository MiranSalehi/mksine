<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Capture;

use Illuminate\Database\Connection;
use PDO;

final class CaptureConnection extends Connection
{
    private ?CaptureSchemaBuilder $captureSchemaBuilder = null;

    /** @var list<string> */
    private array $rawStatements = [];

    public static function registerResolver(Connection $source): void
    {
        self::resolverFor('migrate_smart_capture', function ($connection, $database, $prefix, $config) use ($source) {
            $pdo = new PDO('sqlite::memory:');

            return new self($pdo, $database, $prefix, $config, $source);
        });
    }

    public function __construct(
        PDO $pdo,
        string $database,
        string $prefix,
        array $config,
        private readonly Connection $sourceConnection,
    ) {
        parent::__construct($pdo, $database, $prefix, $config);

        $this->setQueryGrammar($sourceConnection->getQueryGrammar());
        $this->setSchemaGrammar($sourceConnection->getSchemaGrammar());
        $this->setPostProcessor($sourceConnection->getPostProcessor());
    }

    public function getSchemaBuilder(): CaptureSchemaBuilder
    {
        if ($this->captureSchemaBuilder === null) {
            $this->captureSchemaBuilder = new CaptureSchemaBuilder($this);
        }

        return $this->captureSchemaBuilder;
    }

    /**
     * @return list<string>
     */
    public function rawStatements(): array
    {
        return $this->rawStatements;
    }

    public function flushRawStatements(): void
    {
        $this->rawStatements = [];
    }

    public function statement($query, $bindings = []): bool
    {
        $this->rawStatements[] = (string) $query;

        return true;
    }

    public function affectingStatement($query, $bindings = []): int
    {
        $this->rawStatements[] = (string) $query;

        return 0;
    }

    /**
     * @return array<int, object>
     */
    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        return [];
    }

    public function scalar($query, $bindings = [], $useReadPdo = true): mixed
    {
        return null;
    }
}
