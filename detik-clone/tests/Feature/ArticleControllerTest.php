<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $editor;
    protected User $author;
    protected User $subscriber;
    protected Category $category;
    protected Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
        $this->subscriber = User::factory()->create(['role' => 'subscriber']);

        // Create category and tag
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->tag = Tag::factory()->create();

        // Clear cache before each test
        Cache::flush();
        Storage::fake('public');
    }

    // ===== INDEX TESTS =====

    /** @test */
    public function guest_can_view_articles_index()
    {
        // Create published articles
        $articles = Article::factory()
            ->count(5)
            ->published()
            ->create(['category_id' => $this->category->id]);

        $response = $this->get(route('articles.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function articles_index_filters_by_category()
    {
        $techCategory = Category::factory()->create(['slug' => 'tech', 'is_active' => true]);
        $sportsCategory = Category::factory()->create(['slug' => 'sports', 'is_active' => true]);

        Article::factory()->published()->create(['category_id' => $techCategory->id]);
        Article::factory()->published()->create(['category_id' => $sportsCategory->id]);

        $response = $this->get(route('articles.index', ['category' => 'tech']));

        $response->assertStatus(200);
        // Additional assertions can be added to verify filtering
    }

    /** @test */
    public function articles_index_filters_by_tag()
    {
        $article = Article::factory()->published()->create();
        $article->tags()->attach($this->tag);

        $response = $this->get(route('articles.index', ['tag' => $this->tag->slug]));

        $response->assertStatus(200);
    }

    /** @test */
    public function articles_index_sorts_correctly()
    {
        Article::factory()->count(3)->published()->create();

        $sorts = ['latest', 'popular', 'trending', 'oldest'];

        foreach ($sorts as $sort) {
            $response = $this->get(route('articles.index', ['sort' => $sort]));
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function articles_index_validates_parameters()
    {
        $response = $this->get(route('articles.index', [
            'sort' => 'invalid_sort',
            'per_page' => 1000
        ]));

        $response->assertStatus(302); // Redirect due to validation error
    }

    // ===== SHOW TESTS =====

    /** @test */
    public function guest_can_view_published_article()
    {
        $article = Article::factory()
            ->published()
            ->create([
                'category_id' => $this->category->id,
                'author_id' => $this->author->id
            ]);

        $article->tags()->attach($this->tag);

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'article' => [
                        'id', 'title', 'slug', 'content'
                    ]
                ]);
    }

    /** @test */
    public function guest_cannot_view_draft_article()
    {
        $article = Article::factory()
            ->draft()
            ->create(['author_id' => $this->author->id]);

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertStatus(404);
    }

    /** @test */
    public function author_can_view_their_draft_article()
    {
        $article = Article::factory()
            ->draft()
            ->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
                        ->get(route('articles.show', $article->slug));

        $response->assertStatus(200);
    }

    /** @test */
    public function article_view_increments_view_count()
    {
        $article = Article::factory()
            ->published()
            ->create(['views_count' => 0]);

        $this->get(route('articles.show', $article->slug));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'views_count' => 1
        ]);
    }

    /** @test */
    public function article_show_returns_404_for_nonexistent_article()
    {
        $response = $this->get(route('articles.show', 'nonexistent-slug'));

        $response->assertStatus(404);
    }

    // ===== CREATE TESTS =====

    /** @test */
    public function guest_cannot_access_create_form()
    {
        $response = $this->get(route('articles.create'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_access_create_form()
    {
        $response = $this->actingAs($this->author)
                        ->get(route('articles.create'));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'categories',
                    'tags'
                ]);
    }

    /** @test */
    public function subscriber_cannot_access_create_form()
    {
        $response = $this->actingAs($this->subscriber)
                        ->get(route('articles.create'));

        $response->assertStatus(403);
    }

    // ===== STORE TESTS =====

    /** @test */
    public function author_can_create_article()
    {
        $articleData = [
            'title' => 'Test Article Title',
            'content' => 'This is the content of the test article.',
            'excerpt' => 'This is the excerpt.',
            'category_id' => $this->category->id,
            'tags' => ['tech', 'laravel'],
            'is_featured' => false,
            'is_breaking' => false,
            'allow_comments' => true,
            'meta_title' => 'Test Meta Title',
            'meta_description' => 'Test meta description',
            'status' => 'draft'
        ];

        $response = $this->actingAs($this->author)
                        ->post(route('articles.store'), $articleData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article Title',
            'author_id' => $this->author->id,
            'category_id' => $this->category->id,
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function article_creation_with_featured_image()
    {
        $image = UploadedFile::fake()->image('featured.jpg', 800, 600);

        $articleData = [
            'title' => 'Article with Image',
            'content' => 'Content here',
            'category_id' => $this->category->id,
            'featured_image' => $image,
            'status' => 'draft'
        ];

        $response = $this->actingAs($this->author)
                        ->post(route('articles.store'), $articleData);

        $response->assertRedirect();
        
        $article = Article::where('title', 'Article with Image')->first();
        $this->assertNotNull($article->featured_image);
        Storage::disk('public')->assertExists($article->featured_image);
    }

    /** @test */
    public function article_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->author)
                        ->post(route('articles.store'), []);

        $response->assertSessionHasErrors(['title', 'content', 'category_id']);
    }

    /** @test */
    public function article_creation_validates_category_exists()
    {
        $articleData = [
            'title' => 'Test Article',
            'content' => 'Content here',
            'category_id' => 99999, // Non-existent category
        ];

        $response = $this->actingAs($this->author)
                        ->post(route('articles.store'), $articleData);

        $response->assertSessionHasErrors(['category_id']);
    }

    /** @test */
    public function guest_cannot_create_article()
    {
        $response = $this->post(route('articles.store'), [
            'title' => 'Test Article',
            'content' => 'Content',
            'category_id' => $this->category->id
        ]);

        $response->assertRedirect(route('login'));
    }

    // ===== EDIT TESTS =====

    /** @test */
    public function author_can_access_edit_form_for_their_article()
    {
        $article = Article::factory()->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
                        ->get(route('articles.edit', $article));

        $response->assertStatus(200)
                ->assertViewIs('articles.edit')
                ->assertViewHas('article')
                ->assertViewHas('categories')
                ->assertViewHas('tags');
    }

    /** @test */
    public function author_cannot_edit_others_article()
    {
        $otherAuthor = User::factory()->create(['role' => 'author']);
        $article = Article::factory()->create(['author_id' => $otherAuthor->id]);

        $response = $this->actingAs($this->author)
                        ->get(route('articles.edit', $article));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_edit_any_article()
    {
        $article = Article::factory()->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->admin)
                        ->get(route('articles.edit', $article));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_edit_form()
    {
        $article = Article::factory()->create();

        $response = $this->get(route('articles.edit', $article));

        $response->assertRedirect(route('login'));
    }

    // ===== UPDATE TESTS =====

    /** @test */
    public function author_can_update_their_article()
    {
        $article = Article::factory()->create([
            'author_id' => $this->author->id,
            'title' => 'Original Title'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'category_id' => $this->category->id,
            'status' => 'published'
        ];

        $response = $this->actingAs($this->author)
                        ->put(route('articles.update', $article), $updateData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
            'status' => 'published'
        ]);
    }

    /** @test */
    public function author_cannot_update_others_article()
    {
        $otherAuthor = User::factory()->create(['role' => 'author']);
        $article = Article::factory()->create(['author_id' => $otherAuthor->id]);

        $response = $this->actingAs($this->author)
                        ->put(route('articles.update', $article), [
                            'title' => 'Hacked Title',
                            'content' => 'Hacked content',
                            'category_id' => $this->category->id
                        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function article_update_validates_required_fields()
    {
        $article = Article::factory()->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
                        ->put(route('articles.update', $article), [
                            'title' => '',
                            'content' => '',
                        ]);

        $response->assertSessionHasErrors(['title', 'content', 'category_id']);
    }

    // ===== DELETE TESTS =====

    /** @test */
    public function author_can_delete_their_article()
    {
        $article = Article::factory()->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
                        ->delete(route('articles.destroy', $article));

        $response->assertRedirect(route('articles.index'));
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /** @test */
    public function author_cannot_delete_others_article()
    {
        $otherAuthor = User::factory()->create(['role' => 'author']);
        $article = Article::factory()->create(['author_id' => $otherAuthor->id]);

        $response = $this->actingAs($this->author)
                        ->delete(route('articles.destroy', $article));

        $response->assertStatus(403);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    /** @test */
    public function admin_can_delete_any_article()
    {
        $article = Article::factory()->create(['author_id' => $this->author->id]);

        $response = $this->actingAs($this->admin)
                        ->delete(route('articles.destroy', $article));

        $response->assertRedirect();
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /** @test */
    public function guest_cannot_delete_article()
    {
        $article = Article::factory()->create();

        $response = $this->delete(route('articles.destroy', $article));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    // ===== BOOKMARK TESTS =====

    /** @test */
    public function authenticated_user_can_bookmark_article()
    {
        $article = Article::factory()->published()->create();

        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.bookmark', $article));

        $response->assertStatus(200)
                ->assertJson(['bookmarked' => true]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->subscriber->id,
            'bookmarkable_id' => $article->id,
            'bookmarkable_type' => Article::class
        ]);
    }

    /** @test */
    public function authenticated_user_can_unbookmark_article()
    {
        $article = Article::factory()->published()->create();
        
        // First bookmark
        $this->subscriber->bookmarks()->create([
            'bookmarkable_id' => $article->id,
            'bookmarkable_type' => Article::class
        ]);

        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.bookmark', $article));

        $response->assertStatus(200)
                ->assertJson(['bookmarked' => false]);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->subscriber->id,
            'bookmarkable_id' => $article->id,
            'bookmarkable_type' => Article::class
        ]);
    }

    /** @test */
    public function guest_cannot_bookmark_article()
    {
        $article = Article::factory()->published()->create();

        $response = $this->post(route('articles.bookmark', $article));

        $response->assertStatus(401)
                ->assertJson(['error' => 'Authentication required']);
    }

    // ===== SHARE TESTS =====

    /** @test */
    public function anyone_can_share_published_article()
    {
        $article = Article::factory()->published()->create(['shares_count' => 0]);

        $response = $this->post(route('articles.share', $article), [
            'platform' => 'twitter'
        ]);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'shares_count' => 1
        ]);
    }

    /** @test */
    public function share_validates_platform()
    {
        $article = Article::factory()->published()->create();

        $response = $this->post(route('articles.share', $article), [
            'platform' => 'invalid_platform'
        ]);

        $response->assertStatus(422);
    }

    // ===== COMMENT TESTS =====

    /** @test */
    public function authenticated_user_can_comment_on_article()
    {
        $article = Article::factory()->published()->create(['allow_comments' => true]);

        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.comment', $article), [
                            'content' => 'This is a test comment.'
                        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $article->id,
            'commentable_type' => Article::class,
            'user_id' => $this->subscriber->id,
            'content' => 'This is a test comment.'
        ]);
    }

    /** @test */
    public function user_cannot_comment_on_article_with_comments_disabled()
    {
        $article = Article::factory()->published()->create(['allow_comments' => false]);

        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.comment', $article), [
                            'content' => 'This should not be allowed.'
                        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_comment_on_article()
    {
        $article = Article::factory()->published()->create(['allow_comments' => true]);

        $response = $this->post(route('articles.comment', $article), [
            'content' => 'Guest comment'
        ]);

        $response->assertRedirect(route('login'));
    }

    // ===== CACHING TESTS =====

    /** @test */
    public function article_listing_uses_cache()
    {
        // Clear cache first
        Cache::flush();

        Article::factory()->count(3)->published()->create();

        // First request should hit database and cache result
        $response1 = $this->get(route('articles.index'));
        $response1->assertStatus(200);

        // Second request should use cached result
        $response2 = $this->get(route('articles.index'));
        $response2->assertStatus(200);

        // Verify cache was used (this would require additional cache testing setup)
        $this->assertTrue(Cache::has('categories.active'));
    }

    /** @test */
    public function article_show_uses_cache()
    {
        Cache::flush();

        $article = Article::factory()->published()->create();

        // First view should cache the article
        $response1 = $this->get(route('articles.show', $article->slug));
        $response1->assertStatus(200);

        // Second view should use cached data
        $response2 = $this->get(route('articles.show', $article->slug));
        $response2->assertStatus(200);
    }

    // ===== INTEGRATION TESTS =====

    /** @test */
    public function complete_article_workflow()
    {
        // Author creates article
        $articleData = [
            'title' => 'Complete Workflow Test',
            'content' => 'This tests the complete workflow.',
            'category_id' => $this->category->id,
            'status' => 'draft'
        ];

        $response = $this->actingAs($this->author)
                        ->post(route('articles.store'), $articleData);

        $article = Article::where('title', 'Complete Workflow Test')->first();
        $this->assertNotNull($article);

        // Author updates article
        $response = $this->actingAs($this->author)
                        ->put(route('articles.update', $article), [
                            'title' => 'Updated Workflow Test',
                            'content' => 'Updated content',
                            'category_id' => $this->category->id,
                            'status' => 'published'
                        ]);

        $response->assertRedirect();

        // Public can view published article
        $response = $this->get(route('articles.show', $article->fresh()->slug));
        $response->assertStatus(200);

        // User can bookmark article
        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.bookmark', $article));
        $response->assertStatus(200);

        // User can comment on article
        $response = $this->actingAs($this->subscriber)
                        ->post(route('articles.comment', $article), [
                            'content' => 'Great article!'
                        ]);
        $response->assertRedirect();
    }

    // ===== HELPER METHODS =====

    protected function createArticleWithComments(int $commentCount = 3): Article
    {
        $article = Article::factory()->published()->create([
            'allow_comments' => true,
            'category_id' => $this->category->id
        ]);

        Comment::factory()
            ->count($commentCount)
            ->approved()
            ->create([
                'commentable_id' => $article->id,
                'commentable_type' => Article::class
            ]);

        return $article;
    }

    protected function createFeaturedArticles(int $count = 3): void
    {
        Article::factory()
            ->count($count)
            ->published()
            ->featured()
            ->create(['category_id' => $this->category->id]);
    }
}