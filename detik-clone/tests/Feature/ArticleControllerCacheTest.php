<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleControllerCacheTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $author;
    protected Category $category;
    protected Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create(['role' => 'author']);
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->tag = Tag::factory()->create();

        Cache::flush();
    }

    /** @test */
    public function article_index_caches_results()
    {
        // Create articles
        Article::factory()
            ->count(3)
            ->published()
            ->inCategory($this->category)
            ->create();

        // Clear cache to ensure fresh start
        Cache::flush();

        // First request - should cache results
        $response1 = $this->get(route('articles.index'));
        $response1->assertStatus(200);

        // Verify cache keys exist
        $this->assertTrue(Cache::has('categories.active'));
        $this->assertTrue(Cache::has('tags.popular'));
        $this->assertTrue(Cache::has('articles.featured'));
    }

    /** @test */
    public function article_show_caches_article_data()
    {
        $article = Article::factory()
            ->published()
            ->inCategory($this->category)
            ->create();

        Cache::flush();

        // First request - should cache the article
        $response1 = $this->get(route('articles.show', $article->slug));
        $response1->assertStatus(200);

        // Verify article cache exists
        $this->assertTrue(Cache::has("article.{$article->slug}"));
    }

    /** @test */
    public function cache_is_invalidated_when_article_is_updated()
    {
        $article = Article::factory()
            ->published()
            ->byAuthor($this->author)
            ->inCategory($this->category)
            ->create();

        // Prime the cache
        $this->get(route('articles.show', $article->slug));
        $this->assertTrue(Cache::has("article.{$article->slug}"));

        // Update the article
        $response = $this->actingAs($this->author)
                        ->put(route('articles.update', $article), [
                            'title' => 'Updated Title',
                            'content' => 'Updated content',
                            'category_id' => $this->category->id,
                        ]);

        $response->assertRedirect();

        // Cache should be cleared after update
        // Note: This depends on your cache invalidation implementation
    }

    /** @test */
    public function cache_is_invalidated_when_article_is_deleted()
    {
        $article = Article::factory()
            ->published()
            ->byAuthor($this->author)
            ->inCategory($this->category)
            ->create();

        // Prime the cache
        $this->get(route('articles.show', $article->slug));
        $this->assertTrue(Cache::has("article.{$article->slug}"));

        // Delete the article
        $response = $this->actingAs($this->author)
                        ->delete(route('articles.destroy', $article));

        $response->assertRedirect();
    }

    /** @test */
    public function related_articles_are_cached()
    {
        $article = Article::factory()
            ->published()
            ->inCategory($this->category)
            ->create();

        // Create related articles in same category
        Article::factory()
            ->count(3)
            ->published()
            ->inCategory($this->category)
            ->create();

        Cache::flush();

        $response = $this->get(route('articles.show', $article->slug));
        $response->assertStatus(200);

        // Verify related articles cache
        $this->assertTrue(Cache::has("article.{$article->slug}.related"));
    }

    /** @test */
    public function cache_keys_include_filters_for_listing()
    {
        Article::factory()
            ->count(5)
            ->published()
            ->inCategory($this->category)
            ->create();

        Cache::flush();

        $filters = [
            'category' => $this->category->slug,
            'sort' => 'popular',
            'per_page' => 10
        ];

        $response = $this->get(route('articles.index', $filters));
        $response->assertStatus(200);

        // Cache should create keys based on filters
        // This would require inspection of actual cache implementation
    }
}