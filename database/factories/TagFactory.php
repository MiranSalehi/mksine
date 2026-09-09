<?php

namespace Miran\Mksine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Miran\Mksine\Models\Tag;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->paragraph(),
            'is_active' => fake()->boolean(90),
            'meta_title' => fake()->optional(0.5)->sentence(6),
            'meta_description' => fake()->optional(0.5)->paragraph(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
