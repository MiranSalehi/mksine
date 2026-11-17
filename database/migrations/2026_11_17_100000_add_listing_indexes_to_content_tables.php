<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the storefront listing queries.
 *
 * Every post list filters on status and sorts by published_at; single-column indexes made MySQL
 * pick one and filesort the rest. Each index is added only when its table exists and an index
 * on exactly those columns is not already there, so the migration is safe to re-run.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<list<string>>>
     */
    private const INDEXES = [
        'posts' => [
            ['status', 'published_at'],
            ['author_id', 'status', 'published_at'],
        ],
        'pages' => [
            ['status', 'published_at'],
        ],
        'mks_entries' => [
            ['content_type', 'status', 'published_at'],
        ],
        'categories' => [
            ['parent_id', 'is_active', 'sort_order'],
        ],
        'taggables' => [
            ['tag_id', 'taggable_type', 'taggable_id'],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($tableName, $columns) || Schema::hasIndex($tableName, $columns)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns): void {
                    $table->index($columns, $this->indexName($tableName, $columns));
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $name = $this->indexName($tableName, $columns);

                if (! Schema::hasIndex($tableName, $name)) {
                    continue;
                }

                // MySQL lets a foreign key adopt this index (author_id, parent_id); give the key
                // its own index back before dropping, or the drop is refused.
                if (! $this->hasOtherIndexStartingWith($tableName, $columns[0], $name)) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                        $table->index([$columns[0]]);
                    });
                }

                Schema::table($tableName, function (Blueprint $table) use ($name): void {
                    $table->dropIndex($name);
                });
            }
        }
    }

    private function hasOtherIndexStartingWith(string $tableName, string $column, string $exceptName): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if ($index['name'] !== $exceptName && ($index['columns'][0] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function indexName(string $tableName, array $columns): string
    {
        return $tableName . '_' . implode('_', $columns) . '_listing_index';
    }
};
