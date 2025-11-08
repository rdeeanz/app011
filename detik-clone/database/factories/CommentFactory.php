<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph(3),
            'user_id' => User::factory(),
            'commentable_id' => null, // Will be set when attaching to article
            'commentable_type' => null, // Will be set when attaching to article
            'parent_id' => null,
            'status' => 'approved',
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'likes_count' => $this->faker->numberBetween(0, 50),
            'replies_count' => 0,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Create an approved comment
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
            ];
        });
    }

    /**
     * Create a pending comment
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Create a rejected comment
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
            ];
        });
    }

    /**
     * Create a spam comment
     */
    public function spam(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'spam',
            ];
        });
    }

    /**
     * Create a reply comment
     */
    public function replyTo(Comment $parentComment): static
    {
        return $this->state(function (array $attributes) use ($parentComment) {
            return [
                'parent_id' => $parentComment->id,
                'commentable_id' => $parentComment->commentable_id,
                'commentable_type' => $parentComment->commentable_type,
            ];
        });
    }

    /**
     * Create a comment by specific user
     */
    public function byUser(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Create a comment with high likes
     */
    public function popular(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'likes_count' => $this->faker->numberBetween(100, 1000),
            ];
        });
    }
}