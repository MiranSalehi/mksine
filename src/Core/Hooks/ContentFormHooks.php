<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistable content form filters for plugins (custom fields, CPT).
 *
 * Data keys for type: post|page|{content type key}.
 */
final class ContentFormHooks
{
    public const string FILL = 'mksine.content.form_fill';

    public const string MUTATE = 'mksine.content.form_data';

    public const string SAVED = 'mksine.content.saved';

    public const string FORM = 'mksine.content.form';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data, string $type, Model $record): array
    {
        $filtered = Hooks::filter(self::FILL, $data, $type, $record);

        return is_array($filtered) ? $filtered : $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutate(array $data, string $type, ?Model $record = null): array
    {
        $filtered = Hooks::filter(self::MUTATE, $data, $type, $record);

        return is_array($filtered) ? $filtered : $data;
    }

    public static function saved(Model $record, string $type): void
    {
        Hooks::filter(self::SAVED, $record, $type);
    }

    public static function form(Schema $schema, string $type): Schema
    {
        $filtered = Hooks::filter(self::FORM, $schema, $type);

        return $filtered instanceof Schema ? $filtered : $schema;
    }
}
