<?php

declare(strict_types=1);

namespace Miran\Mksine\SmartMigration\Support;

use Illuminate\Database\Schema\ColumnDefinition;

final class ColumnTypeNormalizer
{
    /**
     * @return array{type: string, attributes: array<string, mixed>}
     */
    public static function fromBlueprintColumn(ColumnDefinition $column): array
    {
        $type = (string) ($column->type ?? 'string');

        $attributes = array_filter([
            'nullable' => (bool) ($column->nullable ?? false),
            'default' => $column->default ?? null,
            'length' => $column->length ?? null,
            'precision' => $column->total ?? null,
            'scale' => $column->places ?? null,
            'unsigned' => (bool) ($column->unsigned ?? false),
            'autoIncrement' => (bool) ($column->autoIncrement ?? false),
        ], static fn (mixed $value): bool => $value !== null && $value !== false);

        return ['type' => $type, 'attributes' => $attributes];
    }

    /**
     * @param  array<string, mixed>  $databaseColumn
     */
    public static function fromDatabaseColumn(array $databaseColumn): string
    {
        $typeName = strtolower((string) ($databaseColumn['type_name'] ?? $databaseColumn['type'] ?? ''));

        return match (true) {
            str_contains($typeName, 'int') => 'integer',
            str_contains($typeName, 'char') || str_contains($typeName, 'text') => str_contains($typeName, 'text') ? 'text' : 'string',
            str_contains($typeName, 'decimal') || str_contains($typeName, 'numeric') => 'decimal',
            str_contains($typeName, 'float') || str_contains($typeName, 'double') => 'float',
            str_contains($typeName, 'bool') || str_contains($typeName, 'tinyint(1)') => 'boolean',
            str_contains($typeName, 'json') => 'json',
            str_contains($typeName, 'datetime') || str_contains($typeName, 'timestamp') => 'datetime',
            str_contains($typeName, 'date') => 'date',
            str_contains($typeName, 'time') => 'time',
            default => $typeName,
        };
    }

    /**
     * @param  array<string, mixed>  $expectedAttributes
     * @param  array<string, mixed>  $databaseColumn
     */
    public static function typesMatch(string $expectedType, array $expectedAttributes, array $databaseColumn): bool
    {
        $actual = self::fromDatabaseColumn($databaseColumn);

        if ($expectedType === $actual) {
            return true;
        }

        if ($expectedType === 'string' && in_array($actual, ['string', 'varchar'], true)) {
            return true;
        }

        if ($expectedType === 'integer' && in_array($actual, ['integer', 'bigint'], true)) {
            return true;
        }

        if ($expectedType === 'text' && in_array($actual, ['text', 'string'], true)) {
            return (string) ($databaseColumn['type'] ?? '') !== '' && str_contains(strtolower((string) $databaseColumn['type']), 'text');
        }

        return false;
    }
}
