<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Models\Post;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->string('commentable_type')->nullable()->after('id');
            $table->unsignedBigInteger('commentable_id')->nullable()->after('commentable_type');
        });

        $postClass = Post::class;
        DB::statement('UPDATE comments SET commentable_type = ?, commentable_id = post_id', [$postClass]);

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['post_id']);
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('post_id');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['commentable_type', 'commentable_id', 'status'], 'comments_commentable_status_idx');
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE comments MODIFY commentable_type VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE comments MODIFY commentable_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE comments ALTER COLUMN commentable_type SET NOT NULL');
            DB::statement('ALTER TABLE comments ALTER COLUMN commentable_id SET NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_commentable_status_idx');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->foreignId('post_id')->nullable()->constrained('posts')->cascadeOnDelete();
        });

        $postClass = Post::class;
        DB::table('comments')
            ->where('commentable_type', $postClass)
            ->update(['post_id' => DB::raw('commentable_id')]);

        DB::table('comments')->where('commentable_type', '!=', $postClass)->delete();

        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn(['commentable_type', 'commentable_id']);
        });
    }
};
