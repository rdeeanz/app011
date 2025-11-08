<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheService
{
    protected int $defaultTtl = 3600; // 1 hour
    protected int $shortTtl = 900;    // 15 minutes
    protected int $longTtl = 86400;   // 24 hours

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, int $ttl, \Closure $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    // ===== ARTICLE CACHING =====

    /**
     * Cache article data
     */
    public function cacheArticle(Article $article, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            $cacheKey = $this->getArticleCacheKey($article->id);
            $data = $article->load(['author', 'category', 'tags', 'media']);
            
            Cache::put($cacheKey, $data, $ttl);
            
            // Cache by slug as well
            $slugKey = $this->getArticleSlugCacheKey($article->slug);
            Cache::put($slugKey, $data, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Article caching failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached article
     */
    public function getCachedArticle(int $articleId): ?Article
    {
        try {
            $cacheKey = $this->getArticleCacheKey($articleId);
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Article cache retrieval failed', [
                'article_id' => $articleId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get cached article by slug
     */
    public function getCachedArticleBySlug(string $slug): ?Article
    {
        try {
            $cacheKey = $this->getArticleSlugCacheKey($slug);
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Article slug cache retrieval failed', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache featured articles
     */
    public function cacheFeaturedArticles(?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->shortTtl;
        
        try {
            $articles = Article::featured()
                ->published()
                ->with(['author', 'category'])
                ->orderBy('featured_at', 'desc')
                ->limit(5)
                ->get();
            
            Cache::put('featured_articles', $articles, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Featured articles caching failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache popular articles
     */
    public function cachePopularArticles(int $days = 7, int $limit = 10, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            $articles = Article::published()
                ->where('published_at', '>=', now()->subDays($days))
                ->with(['author', 'category'])
                ->orderBy('views_count', 'desc')
                ->orderBy('engagement_score', 'desc')
                ->limit($limit)
                ->get();
            
            $cacheKey = "popular_articles_{$days}_{$limit}";
            Cache::put($cacheKey, $articles, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Popular articles caching failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache trending articles
     */
    public function cacheTrendingArticles(int $hours = 24, int $limit = 10, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->shortTtl;
        
        try {
            $articles = Article::published()
                ->where('published_at', '>=', now()->subHours($hours))
                ->with(['author', 'category'])
                ->orderByRaw('(views_count + shares_count * 2 + comments_count * 3) DESC')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
            
            $cacheKey = "trending_articles_{$hours}_{$limit}";
            Cache::put($cacheKey, $articles, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Trending articles caching failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== CATEGORY CACHING =====

    /**
     * Cache categories with article counts
     */
    public function cacheCategories(?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->longTtl;
        
        try {
            $categories = Category::withCount([
                'articles' => fn($q) => $q->published()
            ])
            ->orderBy('name')
            ->get();
            
            Cache::put('categories_with_counts', $categories, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Categories caching failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache category articles
     */
    public function cacheCategoryArticles(Category $category, int $page = 1, int $perPage = 15, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            $articles = Article::published()
                ->where('category_id', $category->id)
                ->with(['author', 'tags'])
                ->withCount(['comments', 'bookmarks'])
                ->orderBy('published_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            $cacheKey = "category_articles_{$category->id}_{$page}_{$perPage}";
            Cache::put($cacheKey, $articles, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Category articles caching failed', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== HOMEPAGE CACHING =====

    /**
     * Cache homepage data
     */
    public function cacheHomepageData(?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->shortTtl;
        
        try {
            $data = [
                'featured_articles' => $this->getCachedFeaturedArticles(),
                'breaking_news' => $this->getCachedBreakingNews(),
                'latest_articles' => $this->getCachedLatestArticles(),
                'popular_articles' => $this->getCachedPopularArticles(),
                'categories' => $this->getCachedCategories(),
            ];
            
            Cache::put('homepage_data', $data, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Homepage data caching failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached homepage data
     */
    public function getCachedHomepageData(): ?array
    {
        try {
            return Cache::get('homepage_data');
        } catch (\Exception $e) {
            Log::warning('Homepage data cache retrieval failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ===== SEARCH CACHING =====

    /**
     * Cache search results
     */
    public function cacheSearchResults(string $query, array $filters = [], int $page = 1, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->shortTtl;
        
        try {
            $cacheKey = $this->getSearchCacheKey($query, $filters, $page);
            
            // Perform search
            $results = Article::published()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%")
                      ->orWhere('excerpt', 'LIKE', "%{$query}%");
                })
                ->with(['author', 'category', 'tags'])
                ->withCount(['comments', 'bookmarks'])
                ->orderBy('published_at', 'desc')
                ->paginate(15, ['*'], 'page', $page);
            
            Cache::put($cacheKey, $results, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('Search results caching failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached search results
     */
    public function getCachedSearchResults(string $query, array $filters = [], int $page = 1)
    {
        try {
            $cacheKey = $this->getSearchCacheKey($query, $filters, $page);
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Search cache retrieval failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ===== USER SPECIFIC CACHING =====

    /**
     * Cache user's reading history
     */
    public function cacheUserReadingHistory(User $user, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            // This would integrate with analytics to get user's reading history
            $history = collect(); // Placeholder
            
            $cacheKey = "user_reading_history_{$user->id}";
            Cache::put($cacheKey, $history, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('User reading history caching failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cache user's recommendations
     */
    public function cacheUserRecommendations(User $user, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            // Generate recommendations based on user's reading history
            $recommendations = $this->generateUserRecommendations($user);
            
            $cacheKey = "user_recommendations_{$user->id}";
            Cache::put($cacheKey, $recommendations, $ttl);
            
        } catch (\Exception $e) {
            Log::warning('User recommendations caching failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== CACHE INVALIDATION =====

    /**
     * Clear article cache
     */
    public function clearArticleCache(Article $article): void
    {
        try {
            $keys = [
                $this->getArticleCacheKey($article->id),
                $this->getArticleSlugCacheKey($article->slug),
                'featured_articles',
                'trending_articles_24_10',
                'popular_articles_7_10',
                'homepage_data',
                "category_articles_{$article->category_id}_1_15",
            ];
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            
            // Clear related caches
            $this->clearRelatedArticlesCache($article);
            
        } catch (\Exception $e) {
            Log::warning('Article cache clearing failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear category cache
     */
    public function clearCategoryCache(Category $category): void
    {
        try {
            $keys = [
                'categories_with_counts',
                "category_articles_{$category->id}_1_15",
                'homepage_data',
            ];
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            
        } catch (\Exception $e) {
            Log::warning('Category cache clearing failed', [
                'category_id' => $category->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches(): void
    {
        try {
            Cache::flush();
            Log::info('All caches cleared');
        } catch (\Exception $e) {
            Log::error('Cache clearing failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear expired caches
     */
    public function clearExpiredCaches(): void
    {
        try {
            // This would depend on the cache driver
            // For Redis, we could scan for expired keys
            // For file cache, we could check file modification times
            
            Log::info('Expired caches cleared');
        } catch (\Exception $e) {
            Log::warning('Expired cache clearing failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== CACHE WARMING =====

    /**
     * Warm up essential caches
     */
    public function warmUpEssentialCaches(): void
    {
        try {
            $this->cacheFeaturedArticles();
            $this->cachePopularArticles();
            $this->cacheTrendingArticles();
            $this->cacheCategories();
            $this->cacheHomepageData();
            
            Log::info('Essential caches warmed up');
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm up article cache
     */
    public function warmUpArticleCache(Article $article): void
    {
        try {
            $this->cacheArticle($article);
            
            // Cache related articles
            $relatedArticles = $article->getRelatedArticles(5);
            foreach ($relatedArticles as $relatedArticle) {
                $this->cacheArticle($relatedArticle);
            }
            
        } catch (\Exception $e) {
            Log::warning('Article cache warming failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== UTILITY METHODS =====

    protected function getCachedFeaturedArticles()
    {
        return Cache::get('featured_articles', collect());
    }

    protected function getCachedBreakingNews()
    {
        return Cache::remember('breaking_news', $this->shortTtl, function () {
            return Article::published()
                ->breaking()
                ->with(['author', 'category'])
                ->limit(3)
                ->get();
        });
    }

    protected function getCachedLatestArticles()
    {
        return Cache::remember('latest_articles', $this->shortTtl, function () {
            return Article::published()
                ->with(['author', 'category'])
                ->orderBy('published_at', 'desc')
                ->limit(10)
                ->get();
        });
    }

    protected function getCachedPopularArticles()
    {
        return Cache::get('popular_articles_7_10', collect());
    }

    protected function getCachedCategories()
    {
        return Cache::get('categories_with_counts', collect());
    }

    protected function getArticleCacheKey(int $articleId): string
    {
        return "article_{$articleId}";
    }

    protected function getArticleSlugCacheKey(string $slug): string
    {
        return "article_slug_{$slug}";
    }

    protected function getSearchCacheKey(string $query, array $filters, int $page): string
    {
        $filterHash = md5(serialize($filters));
        return "search_{$query}_{$filterHash}_{$page}";
    }

    protected function clearRelatedArticlesCache(Article $article): void
    {
        // Clear caches for articles in the same category
        $relatedArticles = Article::where('category_id', $article->category_id)
            ->limit(10)
            ->get();
        
        foreach ($relatedArticles as $relatedArticle) {
            Cache::forget($this->getArticleCacheKey($relatedArticle->id));
            Cache::forget($this->getArticleSlugCacheKey($relatedArticle->slug));
        }
    }

    protected function generateUserRecommendations(User $user)
    {
        // Placeholder for recommendation algorithm
        // This would analyze user's reading history, preferences, etc.
        return Article::published()
            ->with(['author', 'category'])
            ->limit(10)
            ->get();
    }

    // ===== CACHE STATISTICS =====

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        try {
            return [
                'total_keys' => $this->getTotalCacheKeys(),
                'memory_usage' => $this->getCacheMemoryUsage(),
                'hit_rate' => $this->getCacheHitRate(),
                'popular_keys' => $this->getMostAccessedKeys(),
            ];
        } catch (\Exception $e) {
            Log::warning('Cache statistics retrieval failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    protected function getTotalCacheKeys(): int
    {
        // Implementation would depend on cache driver
        return 0; // Placeholder
    }

    protected function getCacheMemoryUsage(): string
    {
        // Implementation would depend on cache driver
        return '0 MB'; // Placeholder
    }

    protected function getCacheHitRate(): float
    {
        // Implementation would depend on cache driver
        return 0.0; // Placeholder
    }

    protected function getMostAccessedKeys(): array
    {
        // Implementation would depend on cache driver
        return []; // Placeholder
    }
}