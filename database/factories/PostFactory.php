<?php

namespace Miran\Mksine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Miran\Mksine\Models\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);
        $slug = Str::slug($title);

        return [
            'title' => $title,
            'slug' => $slug,
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(4)).'</p>',
            'excerpt' => fake()->optional(0.8)->paragraph(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'featured_image' => null,
            'author_id' => 1,
            'published_at' => null,
            'meta_title' => fake()->optional(0.6)->sentence(6),
            'meta_description' => fake()->optional(0.6)->paragraph(),
            'views_count' => fake()->numberBetween(0, 5000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'published_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    public function forAuthor(int $authorId): static
    {
        return $this->state(fn (array $attributes) => ['author_id' => $authorId]);
    }
}
