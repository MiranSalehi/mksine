<?php

declare(strict_types=1);

namespace Miran\Mksine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Miran\Mksine\Models\Entry;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'content_type' => 'portfolio',
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'excerpt' => fake()->optional(0.8)->paragraph(),
            'status' => 'draft',
            'featured_image' => null,
            'author_id' => 1,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyphrase' => null,
            'views_count' => 0,
        ];
    }

    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_type' => $type,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function forAuthor(int $authorId): static
    {
        return $this->state(fn (array $attributes): array => [
            'author_id' => $authorId,
        ]);
    }
}
