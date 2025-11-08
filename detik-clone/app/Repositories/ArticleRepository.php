<?php

namespace App\Repositories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArticleRepository
{
    protected Article $model;

    // Optimized eager loading relationships - moved to methods to avoid closure in properties
    protected function getDefaultRelations(): array
    {
        return [
            'author:id,name,username,avatar',
            'category:id,name,slug,color',
            'tags:id,name,slug,color'
        ];
    }

    protected function getFullRelations(): array
    {
        return [
            'author:id,name,username,email,avatar,bio',
            'category:id,name,slug,color,description',
            'tags:id,name,slug,color,description',
            'media',
            'comments' => function ($query) {
                $query->approved()->latest()->limit(5);
            }
        ];
    }

    protected function getDetailRelations(): array
    {
        return [
            'author:id,name,username,email,avatar,bio,created_at',
            'editor:id,name,username,avatar',
            'category:id,name,slug,color,description,parent_id',
            'category.parent:id,name,slug',
            'tags:id,name,slug,color,description',
            'media',
            'comments.user:id,name,username,avatar',
            'comments.approvedReplies.user:id,name,username,avatar'
        ];
    }

    public function __construct(Article $model)
    {
        $this->model = $model;
    }

    // ===== OPTIMIZED BASIC CRUD OPERATIONS =====

    public function find(int $id, bool $withDetails = false): ?Article
    {
        $relations = $withDetails ? $this->getDetailRelations() : $this->getDefaultRelations();
        $cacheKey = $withDetails ? "article:detail:{$id}" : "article:{$id}";
        
        return Cache::tags(['articles'])->remember(
            $cacheKey,
            3600,
            fn() => $this->model->with($relations)->find($id)
        );
    }

    public function findBySlug(string $slug, bool $withDetails = true): ?Article
    {
        $relations = $withDetails ? $this->getDetailRelations() : $this->getDefaultRelations();
        $cacheKey = $withDetails ? "article:slug:detail:{$slug}" : "article:slug:{$slug}";
        
        return Cache::tags(['articles'])->remember(
            $cacheKey,
            3600,
            fn() => $this->model->with($relations)
                ->where('slug', $slug)
                ->first()
        );
    }

    public function create(array $data): Article
    {
        DB::beginTransaction();
        
        try {
            $article = $this->model->create($data);
            
            // Handle tags
            if (isset($data['tags'])) {
                $tagIds = $this->processTagIds($data['tags']);
                $article->tags()->sync($tagIds);
            }
            
            // Handle meta data
            if (isset($data['meta'])) {
                $this->updateMeta($article, $data['meta']);
            }
            
            DB::commit();
            $this->clearCache($article);
            
            return $article->fresh(['author', 'category', 'tags']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Article $article, array $data): Article
    {
        DB::beginTransaction();
        
        try {
            $article->update($data);
            
            // Handle tags
            if (isset($data['tags'])) {
                $tagIds = $this->processTagIds($data['tags']);
                $article->tags()->sync($tagIds);
            }
            
            // Handle meta data
            if (isset($data['meta'])) {
                $this->updateMeta($article, $data['meta']);
            }
            
            DB::commit();
            $this->clearCache($article);
            
            return $article->fresh(['author', 'category', 'tags']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Article $article): bool
    {
        $this->clearCache($article);
        return $article->delete();
    }

    // ===== QUERY METHODS =====

    public function getPublished(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->published()
            ->with(['author', 'category', 'tags'])
            ->withCount(['comments', 'bookmarks'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getFeatured(int $limit = 5): Collection
    {
        return Cache::tags(['articles', 'featured'])->remember(
            'featured_articles',
            1800, // 30 minutes
            fn() => $this->model->featured()
                ->published()
                ->with(['author', 'category'])
                ->orderBy('featured_at', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    public function getPopular(int $days = 7, int $limit = 10): Collection
    {
        return Cache::tags(['articles', 'popular'])->remember(
            "popular_articles:{$days}:{$limit}",
            3600,
            fn() => $this->model->published()
                ->where('published_at', '>=', now()->subDays($days))
                ->with(['author', 'category'])
                ->orderBy('views_count', 'desc')
                ->orderBy('engagement_score', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    public function getTrending(int $hours = 24, int $limit = 10): Collection
    {
        return Cache::tags(['articles', 'trending'])->remember(
            "trending_articles:{$hours}:{$limit}",
            1800,
            fn() => $this->model->published()
                ->where('published_at', '>=', now()->subHours($hours))
                ->with(['author', 'category'])
                ->orderByRaw('(views_count + shares_count * 2 + comments_count * 3) DESC')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    public function getByCategory(Category $category, int $perPage = 15, array $options = []): LengthAwarePaginator
    {
        $cacheKey = "category:{$category->id}:articles:page:" . request('page', 1) . ":per_page:{$perPage}";
        
        return Cache::tags(['articles', 'categories'])->remember(
            $cacheKey,
            900, // 15 minutes
            function () use ($category, $perPage, $options) {
                return $this->model->published()
                    ->byCategory($category->id)
                    ->with($this->getDefaultRelations())
                    ->withCount(['comments', 'bookmarks', 'views'])
                    ->when(isset($options['featured']), fn($q) => $q->featured())
                    ->when(isset($options['trending']), fn($q) => $q->trending())
                    ->orderByDesc('published_at')
                    ->paginate($perPage);
            }
        );
    }

    public function getByTag(Tag $tag, int $perPage = 15, array $options = []): LengthAwarePaginator
    {
        $cacheKey = "tag:{$tag->id}:articles:page:" . request('page', 1) . ":per_page:{$perPage}";
        
        return Cache::tags(['articles', 'tags'])->remember(
            $cacheKey,
            900, // 15 minutes
            function () use ($tag, $perPage, $options) {
                return $this->model->published()
                    ->byTag($tag->id)
                    ->with($this->getDefaultRelations())
                    ->withCount(['comments', 'bookmarks', 'views'])
                    ->when(isset($options['featured']), fn($q) => $q->featured())
                    ->when(isset($options['trending']), fn($q) => $q->trending())
                    ->orderByDesc('published_at')
                    ->paginate($perPage);
            }
        );
    }

    public function getByAuthor(User $author, int $perPage = 15, array $options = []): LengthAwarePaginator
    {
        $cacheKey = "author:{$author->id}:articles:page:" . request('page', 1) . ":per_page:{$perPage}";
        
        return Cache::tags(['articles', 'authors'])->remember(
            $cacheKey,
            1200, // 20 minutes
            function () use ($author, $perPage, $options) {
                return $this->model->published()
                    ->byAuthor($author->id)
                    ->with(array_merge($this->getDefaultRelations(), ['category:id,name,slug,color']))
                    ->withCount(['comments', 'bookmarks', 'views'])
                    ->when(isset($options['featured']), fn($q) => $q->featured())
                    ->when(isset($options['recent']), fn($q) => $q->recent($options['recent']))
                    ->orderByDesc('published_at')
                    ->paginate($perPage);
            }
        );
    }

    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $builder = $this->model->published();

        // Text search
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('content', 'LIKE', "%{$query}%")
                    ->orWhere('excerpt', 'LIKE', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }

        if (isset($filters['tag_ids']) && is_array($filters['tag_ids'])) {
            $builder->whereHas('tags', fn($q) => $q->whereIn('tags.id', $filters['tag_ids']));
        }

        if (isset($filters['author_id'])) {
            $builder->where('author_id', $filters['author_id']);
        }

        if (isset($filters['date_from'])) {
            $builder->where('published_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $builder->where('published_at', '<=', Carbon::parse($filters['date_to']));
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'published_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        if ($sortBy === 'relevance' && !empty($query)) {
            // Simple relevance scoring
            $builder->selectRaw('*, (
                CASE 
                    WHEN title LIKE ? THEN 3
                    WHEN excerpt LIKE ? THEN 2
                    WHEN content LIKE ? THEN 1
                    ELSE 0
                END
            ) as relevance_score', ["%{$query}%", "%{$query}%", "%{$query}%"])
            ->orderBy('relevance_score', 'desc')
            ->orderBy('published_at', 'desc');
        } else {
            $builder->orderBy($sortBy, $sortDirection);
        }

        return $builder->with(['author', 'category', 'tags'])
            ->withCount(['comments', 'bookmarks'])
            ->paginate($perPage);
    }

    public function getRelated(Article $article, int $limit = 5): Collection
    {
        return Cache::tags(['articles'])->remember(
            "related_articles:{$article->id}:{$limit}",
            3600,
            fn() => $article->getRelatedArticles($limit)
        );
    }

    public function getScheduled(): Collection
    {
        return $this->model->scheduled()
            ->with(['author', 'category'])
            ->orderBy('published_at', 'asc')
            ->get();
    }

    public function getPendingReview(): Collection
    {
        return $this->model->pendingReview()
            ->with(['author', 'category'])
            ->orderBy('submitted_for_review_at', 'desc')
            ->get();
    }

    // ===== ANALYTICS METHODS =====

    public function getAnalytics(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subMonth();
        $to = $to ?? now();

        $baseQuery = $this->model->whereBetween('created_at', [$from, $to]);

        return [
            'total_articles' => $baseQuery->count(),
            'published_articles' => $baseQuery->published()->count(),
            'draft_articles' => $baseQuery->draft()->count(),
            'scheduled_articles' => $baseQuery->scheduled()->count(),
            'total_views' => $baseQuery->sum('views_count'),
            'total_shares' => $baseQuery->sum('shares_count'),
            'total_comments' => $baseQuery->sum('comments_count'),
            'average_reading_time' => $baseQuery->avg('reading_time'),
            'top_categories' => $this->getTopCategories($from, $to),
            'top_authors' => $this->getTopAuthors($from, $to),
            'daily_stats' => $this->getDailyStats($from, $to),
        ];
    }

    public function getTopCategories(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return Category::withCount([
                'articles' => fn($q) => $q->published()->whereBetween('published_at', [$from, $to])
            ])
            ->with(['articles' => fn($q) => 
                $q->published()
                    ->whereBetween('published_at', [$from, $to])
                    ->selectRaw('category_id, SUM(views_count) as total_views')
                    ->groupBy('category_id')
            ])
            ->orderBy('articles_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTopAuthors(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return User::withCount([
                'articles' => fn($q) => $q->published()->whereBetween('published_at', [$from, $to])
            ])
            ->with(['articles' => fn($q) => 
                $q->published()
                    ->whereBetween('published_at', [$from, $to])
                    ->selectRaw('author_id, SUM(views_count) as total_views, SUM(shares_count) as total_shares')
                    ->groupBy('author_id')
            ])
            ->orderBy('articles_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getDailyStats(Carbon $from, Carbon $to): Collection
    {
        return $this->model->selectRaw('
                DATE(published_at) as date,
                COUNT(*) as articles_count,
                SUM(views_count) as total_views,
                SUM(shares_count) as total_shares,
                SUM(comments_count) as total_comments
            ')
            ->published()
            ->whereBetween('published_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // ===== UTILITY METHODS =====

    protected function processTagIds(array $tags): array
    {
        $tagIds = [];
        
        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagIds[] = $tag;
            } elseif (is_string($tag)) {
                // Create tag if it doesn't exist
                $tagModel = Tag::firstOrCreate(['name' => $tag], ['slug' => \Str::slug($tag)]);
                $tagIds[] = $tagModel->id;
            }
        }
        
        return array_unique($tagIds);
    }

    protected function updateMeta(Article $article, array $meta): void
    {
        foreach ($meta as $key => $value) {
            \App\Models\ArticleMeta::setMeta($article, $key, $value);
        }
    }

    private function clearCache(Article $article): void
    {
        // Clear specific article caches
        Cache::tags(['articles'])->forget("article:{$article->id}");
        Cache::tags(['articles'])->forget("article:detail:{$article->id}");
        Cache::tags(['articles'])->forget("article:slug:{$article->slug}");
        Cache::tags(['articles'])->forget("article:slug:detail:{$article->slug}");
        
        // Clear comprehensive caches
        $this->clearOptimizedCaches($article);
    }

    // ===== BULK OPERATIONS =====

    public function bulkPublish(array $articleIds, User $user): int
    {
        return $this->model->whereIn('id', $articleIds)
            ->where('editorial_status', 'approved')
            ->update([
                'status' => 'published',
                'editorial_status' => 'published',
                'published_at' => now(),
                'published_by' => $user->id,
            ]);
    }

    public function bulkUnpublish(array $articleIds, User $user): int
    {
        return $this->model->whereIn('id', $articleIds)
            ->where('status', 'published')
            ->update([
                'status' => 'draft',
                'editorial_status' => 'draft',
                'unpublished_at' => now(),
                'unpublished_by' => $user->id,
            ]);
    }

    public function bulkFeature(array $articleIds): int
    {
        return $this->model->whereIn('id', $articleIds)
            ->update([
                'is_featured' => true,
                'featured_at' => now(),
            ]);
    }

    public function bulkArchive(array $articleIds, User $user): int
    {
        return $this->model->whereIn('id', $articleIds)
            ->update([
                'status' => 'archived',
                'editorial_status' => 'archived',
                'archived_at' => now(),
                'archived_by' => $user->id,
            ]);
    }

    // ===== OPTIMIZED QUERY METHODS =====

    /**
     * Get articles with optimized eager loading for listing pages
     */
    public function getOptimizedListing(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->generateListingCacheKey($filters, $perPage);
        
        return Cache::tags(['articles', 'listings'])->remember(
            $cacheKey,
            600, // 10 minutes
            function () use ($filters, $perPage) {
                $query = $this->model->published()
                    ->with($this->getDefaultRelations())
                    ->withCount(['comments', 'bookmarks', 'views']);

                // Apply filters using query scopes
                $this->applyFilters($query, $filters);

                return $query->orderByDesc('published_at')->paginate($perPage);
            }
        );
    }

    /**
     * Get related articles with optimized query
     */
    public function getRelatedArticles(Article $article, int $limit = 4): Collection
    {
        $cacheKey = "article:{$article->id}:related:{$limit}";
        
        return Cache::tags(['articles', 'related'])->remember(
            $cacheKey,
            1800, // 30 minutes
            function () use ($article, $limit) {
                return $this->model->published()
                    ->where('id', '!=', $article->id)
                    ->where(function ($query) use ($article) {
                        $query->where('category_id', $article->category_id)
                              ->orWhereHas('tags', function ($q) use ($article) {
                                  $q->whereIn('tags.id', $article->tags->pluck('id'));
                              });
                    })
                    ->with(['author:id,name,username,avatar', 'category:id,name,slug,color'])
                    ->withCount('views')
                    ->orderByRaw('
                        CASE 
                            WHEN category_id = ? THEN 3
                            ELSE 1
                        END DESC,
                        views_count DESC,
                        published_at DESC
                    ', [$article->category_id])
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get trending articles with optimized performance
     */
    public function getTrendingOptimized(int $hours = 24, int $limit = 10): Collection
    {
        $cacheKey = "trending:optimized:{$hours}:{$limit}";
        
        return Cache::tags(['articles', 'trending'])->remember(
            $cacheKey,
            1800, // 30 minutes
            function () use ($hours, $limit) {
                return $this->model->published()
                    ->recent($hours / 24) // Use days for recent scope
                    ->select([
                        'id', 'title', 'slug', 'excerpt', 'featured_image',
                        'author_id', 'category_id', 'published_at', 'views_count'
                    ])
                    ->with([
                        'author:id,name,username,avatar',
                        'category:id,name,slug,color'
                    ])
                    ->orderByRaw('(views_count * 0.4 + comments_count * 0.6) DESC')
                    ->orderByDesc('published_at')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get popular articles in category with optimized query
     */
    public function getPopularInCategory(int $categoryId, int $days = 7, int $limit = 5): Collection
    {
        $cacheKey = "popular:category:{$categoryId}:days:{$days}:limit:{$limit}";
        
        return Cache::tags(['articles', 'popular', 'categories'])->remember(
            $cacheKey,
            1800, // 30 minutes
            function () use ($categoryId, $days, $limit) {
                return $this->model->published()
                    ->byCategory($categoryId)
                    ->recent($days)
                    ->select([
                        'id', 'title', 'slug', 'featured_image', 
                        'author_id', 'published_at', 'views_count'
                    ])
                    ->with(['author:id,name,username,avatar'])
                    ->orderByDesc('views_count')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get latest articles with minimal data for sidebars
     */
    public function getLatestMinimal(int $limit = 5, ?int $excludeId = null): Collection
    {
        $cacheKey = "latest:minimal:{$limit}:" . ($excludeId ?? 'all');
        
        return Cache::tags(['articles', 'latest'])->remember(
            $cacheKey,
            600, // 10 minutes
            function () use ($limit, $excludeId) {
                return $this->model->published()
                    ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                    ->select(['id', 'title', 'slug', 'featured_image', 'published_at'])
                    ->orderByDesc('published_at')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Get articles count by status with caching
     */
    public function getStatusCounts(): array
    {
        return Cache::tags(['articles', 'stats'])->remember(
            'article:status:counts',
            300, // 5 minutes
            function () {
                return [
                    'total' => $this->model->count(),
                    'published' => $this->model->published()->count(),
                    'draft' => $this->model->draft()->count(),
                    'pending' => $this->model->pendingReview()->count(),
                    'scheduled' => $this->model->scheduled()->count(),
                    'featured' => $this->model->featured()->count(),
                    'breaking' => $this->model->breaking()->count(),
                ];
            }
        );
    }

    // ===== HELPER METHODS =====

    /**
     * Apply filters to query using scopes
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['tag'])) {
            $query->byTag($filters['tag']);
        }

        if (!empty($filters['author'])) {
            $query->byAuthor($filters['author']);
        }

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['breaking'])) {
            $query->breaking();
        }

        if (!empty($filters['recent_days'])) {
            $query->recent($filters['recent_days']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // Sorting
        match ($filters['sort'] ?? 'latest') {
            'popular' => $query->orderByDesc('views_count'),
            'trending' => $query->orderByRaw('(views_count + comments_count * 2) DESC'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->orderByDesc('published_at')
        };
    }

    /**
     * Generate cache key for listing queries
     */
    private function generateListingCacheKey(array $filters, int $perPage): string
    {
        $page = request('page', 1);
        $filterHash = md5(serialize($filters));
        
        return "articles:listing:{$filterHash}:page:{$page}:per_page:{$perPage}";
    }

    /**
     * Clear optimized caches when article is updated
     */
    protected function clearOptimizedCaches(Article $article): void
    {
        $tags = [
            'articles',
            'listings', 
            'related',
            'trending',
            'popular',
            'latest',
            'stats',
            'categories',
            'tags',
            'authors'
        ];

        Cache::tags($tags)->flush();
    }
}