<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArticleControllerApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Category $category;
    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->article = Article::factory()
            ->published()
            ->inCategory($this->category)
            ->create();
    }

    /** @test */
    public function api_can_return_article_json()
    {
        $response = $this->getJson("/api/articles/{$this->article->slug}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'content',
                        'featured_image',
                        'published_at',
                        'reading_time',
                        'views_count',
                        'author' => [
                            'id',
                            'name',
                            'username',
                            'avatar'
                        ],
                        'category' => [
                            'id',
                            'name',
                            'slug',
                            'color'
                        ],
                        'tags'
                    ]
                ]);
    }

    /** @test */
    public function api_can_return_articles_listing_json()
    {
        Article::factory()
            ->count(3)
            ->published()
            ->inCategory($this->category)
            ->create();

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'excerpt',
                            'featured_image',
                            'published_at',
                            'reading_time',
                            'views_count',
                            'author',
                            'category'
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /** @test */
    public function api_bookmark_endpoint_works()
    {
        $response = $this->actingAs($this->user)
                        ->postJson("/api/articles/{$this->article->id}/bookmark");

        $response->assertStatus(200)
                ->assertJson(['bookmarked' => true]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'bookmarkable_id' => $this->article->id,
            'bookmarkable_type' => Article::class
        ]);
    }

    /** @test */
    public function api_share_endpoint_works()
    {
        $initialShares = $this->article->shares_count;

        $response = $this->postJson("/api/articles/{$this->article->id}/share", [
            'platform' => 'twitter'
        ]);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('articles', [
            'id' => $this->article->id,
            'shares_count' => $initialShares + 1
        ]);
    }

    /** @test */
    public function api_validates_share_platform()
    {
        $response = $this->postJson("/api/articles/{$this->article->id}/share", [
            'platform' => 'invalid_platform'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['platform']);
    }

    /** @test */
    public function api_requires_authentication_for_bookmark()
    {
        $response = $this->postJson("/api/articles/{$this->article->id}/bookmark");

        $response->assertStatus(401);
    }

    /** @test */
    public function api_can_filter_articles_by_category()
    {
        $techCategory = Category::factory()->create(['slug' => 'tech']);
        Article::factory()->published()->inCategory($techCategory)->create();

        $response = $this->getJson('/api/articles?category=tech');

        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_search_articles()
    {
        Article::factory()->published()->create(['title' => 'Laravel Tutorial']);
        Article::factory()->published()->create(['title' => 'Vue.js Guide']);

        $response = $this->getJson('/api/articles/search?q=Laravel');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'excerpt'
                        ]
                    ]
                ]);
    }
}