<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6, true);
        $content = $this->faker->paragraphs(5, true);
        
        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->randomNumber(4),
            'excerpt' => $this->faker->text(200),
            'content' => $content,
            'featured_image' => $this->getRandomFeaturedImage(),
            'featured_image_caption' => null,
            'content_type' => $this->faker->randomElement(['text', 'video', 'gallery']),
            'reading_time' => $this->calculateReadingTime($content),
            'word_count' => str_word_count(strip_tags($content)),
            
            // Relationships
            'author_id' => User::factory(),
            'editor_id' => null,
            'category_id' => Category::factory(),
            
            // Status and publishing
            'status' => 'draft',
            'editorial_status' => 'draft',
            'published_at' => null,
            'scheduled_at' => null,
            'expires_at' => null,
            
            // Flags
            'is_featured' => false,
            'is_breaking' => false,
            'is_editors_pick' => false,
            'is_sponsored' => false,
            'is_premium' => false,
            'allow_comments' => true,
            'auto_generated' => false,
            
            // Metrics
            'views_count' => $this->faker->numberBetween(0, 1000),
            'shares_count' => $this->faker->numberBetween(0, 100),
            'likes_count' => $this->faker->numberBetween(0, 200),
            'comments_count' => $this->faker->numberBetween(0, 50),
            'bookmarks_count' => $this->faker->numberBetween(0, 30),
            'engagement_rate' => $this->faker->randomFloat(2, 0, 100),
            
            // SEO
            'meta_title' => $this->faker->sentence(8),
            'meta_description' => $this->faker->text(160),
            'meta_keywords' => implode(',', $this->faker->words(5)),
            
            // Localization
            'language' => 'id',
            'original_article_id' => null,
            
            // Timestamps
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a published article
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            $publishedAt = $this->faker->dateTimeBetween('-6 months', 'now');
            
            return [
                'status' => 'published',
                'editorial_status' => 'approved',
                'published_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ];
        });
    }

    /**
     * Create a draft article
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'editorial_status' => 'draft',
                'published_at' => null,
            ];
        });
    }

    /**
     * Create a scheduled article
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'editorial_status' => 'approved',
                'published_at' => $this->faker->dateTimeBetween('now', '+1 month'),
                'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            ];
        });
    }

    /**
     * Create a featured article
     */
    public function featured(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_featured' => true,
                'featured_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    /**
     * Create a breaking news article
     */
    public function breaking(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_breaking' => true,
                'breaking_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            ];
        });
    }

    /**
     * Create an editor's pick article
     */
    public function editorsPick(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_editors_pick' => true,
                'editors_pick_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Create a sponsored article
     */
    public function sponsored(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sponsored' => true,
                'sponsor_name' => $this->faker->company(),
                'sponsor_url' => $this->faker->url(),
            ];
        });
    }

    /**
     * Create a premium article
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_premium' => true,
            ];
        });
    }

    /**
     * Create an article with high engagement
     */
    public function popular(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'views_count' => $this->faker->numberBetween(5000, 50000),
                'shares_count' => $this->faker->numberBetween(100, 1000),
                'likes_count' => $this->faker->numberBetween(500, 5000),
                'comments_count' => $this->faker->numberBetween(50, 500),
                'bookmarks_count' => $this->faker->numberBetween(100, 1000),
                'engagement_rate' => $this->faker->randomFloat(2, 80, 100),
            ];
        });
    }

    /**
     * Create an article with featured image
     */
    public function withFeaturedImage(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'featured_image' => 'articles/featured/' . $this->faker->uuid() . '.jpg',
                'featured_image_caption' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Create an article with specific author
     */
    public function byAuthor(User $author): static
    {
        return $this->state(function (array $attributes) use ($author) {
            return [
                'author_id' => $author->id,
            ];
        });
    }

    /**
     * Create an article in specific category
     */
    public function inCategory(Category $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category_id' => $category->id,
            ];
        });
    }

    /**
     * Create an article with comments disabled
     */
    public function noComments(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'allow_comments' => false,
            ];
        });
    }

    /**
     * Create an article with specific language
     */
    public function inLanguage(string $language): static
    {
        return $this->state(function (array $attributes) use ($language) {
            return [
                'language' => $language,
            ];
        });
    }

    /**
     * Create a translated article
     */
    public function translationOf(Article $originalArticle): static
    {
        return $this->state(function (array $attributes) use ($originalArticle) {
            return [
                'original_article_id' => $originalArticle->id,
                'category_id' => $originalArticle->category_id,
            ];
        });
    }

    /**
     * Calculate estimated reading time
     */
    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $wordsPerMinute = 200; // Average reading speed
        
        return max(1, ceil($wordCount / $wordsPerMinute));
    }

    /**
     * Get a random featured image from Unsplash
     */
    private function getRandomFeaturedImage(): string
    {
        $images = [
            // Technology
            'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1573164713714-d95e436ab8d6?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1581092795442-32d7b73bc1ad?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&h=600&fit=crop',
            // News/Media
            'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1586776235017-d25c31de90a0?w=800&h=600&fit=crop',
            // Business/Economy
            'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&h=600&fit=crop',
            // Politics/Government
            'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1495435229349-e86db7bfa013?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=800&h=600&fit=crop',
            // Sports
            'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1577223625816-7546f13df25d?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1579952363873-27d3bfad9c0d?w=800&h=600&fit=crop',
            // Health/Medical
            'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1584362917165-526a968579e8?w=800&h=600&fit=crop',
            // Education
            'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&h=600&fit=crop',
        ];

        return $this->faker->randomElement($images);
    }
}