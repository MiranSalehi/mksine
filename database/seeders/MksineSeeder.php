<?php

namespace Miran\Mksine\Database\Seeders;

use Illuminate\Database\Seeder;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;

class MksineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userClass = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userClass::first();

        if (! $user) {
            $this->command->warn('No user found. Run User factory or DatabaseSeeder first.');

            return;
        }

        $userId = $user->id;

        // Categories: 100
        $allCategories = Category::factory()->count(100)->create();

        // Pages: 100 (mix of types and statuses)
        Page::factory()->count(45)->simple()->published()->forUser($userId)->create();
        Page::factory()->count(25)->simple()->draft()->forUser($userId)->create();
        Page::factory()->count(20)->builder()->published()->forUser($userId)->create();
        Page::factory()->count(10)->scheduled()->forUser($userId)->create();

        // Posts: 100 (mix of statuses)
        $posts = Post::factory()->count(70)->published()->forAuthor($userId)->create();
        Post::factory()->count(20)->draft()->forAuthor($userId)->create();
        Post::factory()->count(10)->archived()->forAuthor($userId)->create();

        // Attach categories to posts
        $allPosts = Post::all();
        foreach ($allPosts as $post) {
            $post->categories()->attach(
                $allCategories->random(rand(1, 5))->pluck('id'),
                ['sort_order' => 0]
            );
        }

        $this->command->info('MKSine seed data created: 100 Categories, 100 Pages, 100 Posts.');
    }
}
