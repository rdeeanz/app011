<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->word();
        
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'icon' => null,
            'articles_count' => $this->faker->numberBetween(0, 100),
            'is_trending' => $this->faker->boolean(20),
            'is_featured' => $this->faker->boolean(10),
            'category' => $this->faker->randomElement(['general', 'technology', 'business', 'science', 'health']),
            'popularity_score' => $this->faker->randomFloat(2, 0, 100),
            'seo_title' => $this->faker->sentence(4) . ' - Articles and News',
            'seo_description' => 'Explore articles tagged with ' . ucfirst($name) . '. Stay updated with the latest news and insights on ' . ucfirst($name) . '.',
        ];
    }

    /**
     * Create a trending tag
     */
    public function trending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_trending' => true,
                'articles_count' => $this->faker->numberBetween(50, 200),
            ];
        });
    }

    /**
     * Create a popular tag with high article count
     */
    public function popular(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'articles_count' => $this->faker->numberBetween(100, 500),
            ];
        });
    }

    /**
     * Create technology-related tags
     */
    public function technology(): static
    {
        $techTags = ['Laravel', 'PHP', 'JavaScript', 'Vue', 'React', 'Node.js', 'Python', 'AI', 'Machine Learning'];
        
        return $this->state(function (array $attributes) use ($techTags) {
            $name = $this->faker->randomElement($techTags);
            
            return [
                'name' => $name,
                'slug' => Str::slug($name),
                'color' => '#3B82F6', // Blue color for tech tags
            ];
        });
    }
}