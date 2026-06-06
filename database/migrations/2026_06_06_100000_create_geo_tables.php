<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('geo_countries')) {
            Schema::create('geo_countries', function (Blueprint $table): void {
                $table->unsignedMediumInteger('id')->primary();
                $table->char('iso2', 2)->unique();
                $table->char('iso3', 3)->nullable();
                $table->string('name');
                $table->string('native')->nullable();
                $table->json('translations')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('geo_states')) {
            Schema::create('geo_states', function (Blueprint $table): void {
                $table->unsignedMediumInteger('id')->primary();
                $table->unsignedMediumInteger('geo_country_id')->index();
                $table->string('code', 16)->nullable();
                $table->string('name');
                $table->string('native')->nullable();
                $table->json('translations')->nullable();
                $table->string('source', 16)->default('seed')->index();
                $table->boolean('is_visible')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('geo_country_id')
                    ->references('id')
                    ->on('geo_countries')
                    ->cascadeOnDelete();

                $table->index(['geo_country_id', 'name']);
            });
        }

        if (! Schema::hasTable('geo_cities')) {
            Schema::create('geo_cities', function (Blueprint $table): void {
                $table->unsignedInteger('id')->primary();
                $table->unsignedMediumInteger('geo_state_id')->index();
                $table->unsignedMediumInteger('geo_country_id')->index();
                $table->string('name');
                $table->string('native')->nullable();
                $table->json('translations')->nullable();
                $table->string('source', 16)->default('seed')->index();
                $table->boolean('is_visible')->default(true)->index();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('timezone', 64)->nullable();
                $table->string('wiki_data_id', 32)->nullable();
                $table->string('type', 32)->nullable();
                $table->unsignedInteger('population')->nullable();
                $table->timestamps();

                $table->foreign('geo_state_id')
                    ->references('id')
                    ->on('geo_states')
                    ->cascadeOnDelete();

                $table->foreign('geo_country_id')
                    ->references('id')
                    ->on('geo_countries')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_cities');
        Schema::dropIfExists('geo_states');
        Schema::dropIfExists('geo_countries');
    }
};
