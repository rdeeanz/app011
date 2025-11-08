<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'color' => $this->faker->hexColor(),
            'icon' => null,
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(30),
            'articles_count' => $this->faker->numberBetween(0, 100),
            'seo_title' => $this->faker->sentence(6),
            'seo_description' => $this->faker->text(160),
        ];
    }

    /**
     * Create an inactive category
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Create a category with metadata
     */
    public function withMeta(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meta' => [
                    'is_featured' => $this->faker->boolean(30),
                    'meta_title' => $this->faker->sentence(6),
                    'meta_description' => $this->faker->text(160),
                    'seo_title' => $this->faker->sentence(6),
                    'seo_description' => $this->faker->text(160),
                    'articles_count' => $this->faker->numberBetween(0, 100),
                ],
            ];
        });
    }

    /**
     * Create a child category
     */
    public function childOf(Category $parent): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            return [
                'parent_id' => $parent->id,
            ];
        });
    }

    /**
     * Create a category with icon
     */
    public function withIcon(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'icon' => 'categories/icons/' . $this->faker->uuid() . '.png',
            ];
        });
    }

    /**
     * Create a category with SEO data (stored in meta JSON field)
     */
    public function withSeo(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meta' => [
                    'meta_title' => $this->faker->sentence(6),
                    'meta_description' => $this->faker->text(160),
                    'seo_title' => $this->faker->sentence(6),
                    'seo_description' => $this->faker->text(160),
                ],
            ];
        });
    }
}