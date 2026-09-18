<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mks_entries', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 64)->index();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->string('featured_image')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('focus_keyphrase', 191)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['content_type', 'slug']);
            $table->index(['content_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mks_entries');
    }
};
