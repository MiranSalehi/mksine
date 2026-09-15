<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['posts', 'pages', 'categories', 'tags'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'focus_keyphrase')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $after = Schema::hasColumn($table, 'meta_description') ? 'meta_description' : null;

                $column = $blueprint->string('focus_keyphrase', 191)->nullable();

                if ($after !== null) {
                    $column->after($after);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['posts', 'pages', 'categories', 'tags'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'focus_keyphrase')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('focus_keyphrase');
            });
        }
    }
};
