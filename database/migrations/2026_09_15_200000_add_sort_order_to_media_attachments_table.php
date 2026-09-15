<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_attachments', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('collection_name');
            $table->index(
                ['mediable_type', 'mediable_id', 'collection_name', 'sort_order'],
                'media_attachments_mediable_sort_index',
            );
        });

        DB::table('media_attachments')->orderBy('id')->update([
            'sort_order' => DB::raw('id'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_attachments', function (Blueprint $table) {
            $table->dropIndex('media_attachments_mediable_sort_index');
            $table->dropColumn('sort_order');
        });
    }
};
