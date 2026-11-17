<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Responsive WebP copies of an image: {"640": "media/…/photo-640w.webp", …}.
     */
    public function up(): void
    {
        if (! Schema::hasTable('media') || Schema::hasColumn('media', 'conversions')) {
            return;
        }

        Schema::table('media', function (Blueprint $table): void {
            $table->json('conversions')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('media') && Schema::hasColumn('media', 'conversions')) {
            Schema::table('media', function (Blueprint $table): void {
                $table->dropColumn('conversions');
            });
        }
    }
};
