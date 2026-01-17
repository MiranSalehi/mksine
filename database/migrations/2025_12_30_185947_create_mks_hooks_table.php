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
        Schema::create('mks_hooks', function (Blueprint $table) {
            $table->id();
            $table->string('hook_type')->default('event');
            $table->string('event_name')->nullable()->index();
            $table->string('hook_name')->nullable();
            $table->string('listener_class')->unique();
            $table->integer('priority')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['hook_type', 'hook_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mks_hooks');
    }
};
