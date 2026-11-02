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
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }

            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }

            if (! Schema::hasColumn('users', 'phone_country_code')) {
                $table->string('phone_country_code', 8)->nullable();
            }

            if (! Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 32)->nullable();
            }
        });
    }

    /**
     * Left empty on purpose: these columns hold customer data and other code uses them.
     */
    public function down(): void
    {
        //
    }
};
