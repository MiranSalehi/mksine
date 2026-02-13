<?php

namespace Miran\Mksine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Miran\Mksine\Models\Page;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);
        $slug = Str::slug($title);

        return [
            'title' => $title,
            'slug' => $slug,
            'type' => fake()->randomElement(['simple', 'builder']),
            'status' => fake()->randomElement(['draft', 'published', 'scheduled']),
            'content' => fake()->optional(0.8)->passthrough('<p>'.implode('</p><p>', fake()->paragraphs(2)).'</p>'),
            'builder_payload' => null,
            'meta_title' => fake()->optional(0.6)->sentence(6),
            'meta_description' => fake()->optional(0.6)->paragraph(),
            'published_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'simple',
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'builder_payload' => null,
        ]);
    }

    public function builder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'builder',
            'content' => null,
            'builder_payload' => [
                'blocks' => [
                    ['type' => 'heading', 'data' => ['text' => fake()->sentence()]],
                    ['type' => 'paragraph', 'data' => ['text' => fake()->paragraph()]],
                ],
            ],
        ]);
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

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
