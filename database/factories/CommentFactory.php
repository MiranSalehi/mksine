<?php

namespace Miran\Mksine\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory()->published(),
            'user_id' => null,
            'parent_id' => null,
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'content' => fake()->paragraphs(random_int(1, 3), true),
            'rating' => fake()->optional(0.25)->numberBetween(1, 5),
            'status' => fake()->randomElement([
                Comment::STATUS_APPROVED,
                Comment::STATUS_APPROVED,
                Comment::STATUS_PENDING,
            ]),
            'ip_address' => fake()->optional(0.5)->ipv4(),
            'user_agent' => fake()->optional(0.3)->userAgent(),
        ];
    }

    public function forPost(Post|int $post): static
    {
        $id = $post instanceof Post ? $post->id : $post;

        return $this->state(fn (array $attributes) => [
            'post_id' => $id,
        ]);
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_APPROVED,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function asSpam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Comment::STATUS_SPAM,
        ]);
    }

    public function asGuest(string $name, string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'author_name' => $name,
            'author_email' => $email,
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
            'author_name' => null,
            'author_email' => null,
        ]);
    }
}
