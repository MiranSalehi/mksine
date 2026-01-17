<?php

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
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');
            $table->morphs('mediable'); // This already creates an index on mediable_type and mediable_id
            $table->string('alt')->nullable();
            $table->string('collection_name')->nullable();
            $table->timestamps();

            // No need to add index for mediable_type and mediable_id as morphs() already creates it
            $table->index('collection_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
    }
};
