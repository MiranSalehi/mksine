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
        Schema::create('mks_plugins', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('plugin_id')->unique();

            // State
            $table->enum('status', ['installed', 'active', 'inactive'])->default('installed');

            // Settings (plugin-specific configuration)
            $table->json('settings')->nullable();

            // Lifecycle timestamps
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();

            // Boot guard: tracks if plugin crashed during boot
            $table->boolean('boot_failed')->default(false);
            $table->text('boot_error')->nullable();
            $table->timestamp('boot_failed_at')->nullable();

            // Audit
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mks_plugins');
    }
};
