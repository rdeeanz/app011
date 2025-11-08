<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Tag Model - Advanced Article Tagging System
 * 
 * Features:
 * - Flexible tagging with trending analysis
 * - SEO optimization and rich metadata
 * - Popularity tracking and analytics
 * - Smart categorization and grouping
 * - Performance optimized with caching
 * - Content discovery and recommendations
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'articles_count',
        'is_trending',
        'is_featured',
        'category',
        'popularity_score',
        'seo_title',
        'seo_description',
        'meta',
    ];

    protected $casts = [
        'is_trending' => 'boolean',
        'is_featured' => 'boolean',
        'articles_count' => 'integer',
        'popularity_score' => 'float',
        'meta' => 'array',
    ];

    protected $attributes = [
        'color' => '#6B7280',
        'articles_count' => 0,
        'is_trending' => false,
        'is_featured' => false,
        'popularity_score' => 0.0,
    ];

    protected $appends = [
        'url',
        'display_name',
        'usage_count',
        'trending_score',
    ];

    // ===== RELATIONSHIPS =====

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }

    public function publishedArticles(): BelongsToMany
    {
        return $this->articles()->where('status', 'published');
    }

    public function featuredArticles(): BelongsToMany
    {
        return $this->publishedArticles()->where('is_featured', true);
    }

    // ===== ACCESSORS =====

    public function getUrlAttribute(): string
    {
        return route('tags.show', $this->slug);
    }

    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    public function getUsageCountAttribute(): int
    {
        return Cache::remember("tag_{$this->id}_usage_count", 3600, function () {
            return $this->publishedArticles()->count();
        });
    }

    public function getTrendingScoreAttribute(): float
    {
        $recentDays = 7;
        $recentUsage = $this->publishedArticles()
            ->where('published_at', '>=', now()->subDays($recentDays))
            ->count();
        
        $totalUsage = $this->usage_count;
        
        if ($totalUsage === 0) {
            return 0.0;
        }
        
        $recencyFactor = $recentUsage / $totalUsage;
        $popularityFactor = log($totalUsage + 1);
        
        return round($recencyFactor * $popularityFactor * 100, 2);
    }

    // ===== QUERY SCOPES =====

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true)
                    ->orderBy('popularity_score', 'desc');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->withCount('publishedArticles')
                    ->orderBy('published_articles_count', 'desc');
    }

    public function scopeWithArticleCount(Builder $query): Builder
    {
        return $query->withCount(['publishedArticles as articles_count']);
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->whereHas('publishedArticles');
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->whereDoesntHave('publishedArticles');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->whereHas('publishedArticles', function ($q) use ($days) {
            $q->where('published_at', '>=', now()->subDays($days));
        });
    }

    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function scopeByPopularity(Builder $query): Builder
    {
        return $query->orderBy('popularity_score', 'desc')
                    ->orderBy('articles_count', 'desc');
    }

    // ===== HELPER METHODS =====

    public function updateArticlesCount(): void
    {
        $count = $this->publishedArticles()->count();
        $this->update(['articles_count' => $count]);
        Cache::forget("tag_{$this->id}_usage_count");
    }

    public function updatePopularityScore(): void
    {
        $totalArticles = $this->publishedArticles()->count();
        $recentArticles = $this->publishedArticles()
            ->where('published_at', '>=', now()->subDays(30))
            ->count();
        
        $engagementScore = $this->publishedArticles()
            ->avg('engagement_score') ?: 0;
        
        $viewsScore = $this->publishedArticles()
            ->avg('views_count') ?: 0;
        
        // Calculate popularity score
        $recencyWeight = $recentArticles > 0 ? log($recentArticles + 1) : 0;
        $totalWeight = $totalArticles > 0 ? log($totalArticles + 1) : 0;
        $engagementWeight = $engagementScore / 100;
        $viewsWeight = log($viewsScore + 1) / 100;
        
        $popularityScore = ($recencyWeight * 0.4) + 
                          ($totalWeight * 0.3) + 
                          ($engagementWeight * 0.2) + 
                          ($viewsWeight * 0.1);
        
        $this->update(['popularity_score' => round($popularityScore, 2)]);
    }

    public function markAsTrending(): bool
    {
        $this->update(['is_trending' => true]);
        $this->clearRelatedCaches();
        return true;
    }

    public function unmarkAsTrending(): bool
    {
        $this->update(['is_trending' => false]);
        $this->clearRelatedCaches();
        return true;
    }

    public function toggleTrending(): bool
    {
        return $this->is_trending ? $this->unmarkAsTrending() : $this->markAsTrending();
    }

    public function feature(): bool
    {
        $this->update(['is_featured' => true]);
        $this->clearRelatedCaches();
        return true;
    }

    public function unfeature(): bool
    {
        $this->update(['is_featured' => false]);
        $this->clearRelatedCaches();
        return true;
    }

    public function toggleFeature(): bool
    {
        return $this->is_featured ? $this->unfeature() : $this->feature();
    }

    public function getLatestArticles(int $limit = 10)
    {
        return $this->publishedArticles()
                   ->latest('published_at')
                   ->limit($limit)
                   ->get();
    }

    public function getPopularArticles(int $limit = 10)
    {
        return $this->publishedArticles()
                   ->orderBy('views_count', 'desc')
                   ->orderBy('engagement_score', 'desc')
                   ->limit($limit)
                   ->get();
    }

    public function getRelatedTags(int $limit = 10)
    {
        // Get tags that appear together with this tag in articles
        return static::whereHas('articles', function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->where('tags.id', $this->id);
                });
            })
            ->where('id', '!=', $this->id)
            ->withCount('publishedArticles')
            ->orderBy('published_articles_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSimilarTags(int $limit = 5)
    {
        // Simple similarity based on name
        $name = strtolower($this->name);
        
        return static::where('id', '!=', $this->id)
            ->where(function ($query) use ($name) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$name}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$name}%"]);
            })
            ->orderBy('popularity_score', 'desc')
            ->limit($limit)
            ->get();
    }

    public function hasArticles(): bool
    {
        return $this->publishedArticles()->exists();
    }

    public function isPopular(): bool
    {
        return $this->popularity_score > 5.0 || $this->articles_count > 10;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    private function clearRelatedCaches(): void
    {
        Cache::forget("tag_{$this->id}_usage_count");
        Cache::forget('trending_tags');
        Cache::forget('popular_tags');
        Cache::forget('featured_tags');
    }

    // ===== STATIC METHODS =====

    public static function findByName(string $name): ?self
    {
        $slug = Str::slug($name);
        return static::where('slug', $slug)->orWhere('name', $name)->first();
    }

    public static function createOrFind(string $name): self
    {
        $tag = static::findByName($name);
        
        if (!$tag) {
            $tag = static::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }
        
        return $tag;
    }

    public static function getTrendingTags(int $limit = 10)
    {
        return Cache::remember('trending_tags', 1800, function () use ($limit) {
            return static::trending()
                ->limit($limit)
                ->get();
        });
    }

    public static function getPopularTags(int $limit = 20)
    {
        return Cache::remember('popular_tags', 3600, function () use ($limit) {
            return static::popular()
                ->limit($limit)
                ->get();
        });
    }

    public static function getFeaturedTags(int $limit = 10)
    {
        return Cache::remember('featured_tags', 3600, function () use ($limit) {
            return static::featured()
                ->orderBy('popularity_score', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    public static function updateTrendingTags(): void
    {
        // Calculate trending tags based on recent activity
        $trendingThreshold = 3.0;
        
        static::chunk(100, function ($tags) use ($trendingThreshold) {
            foreach ($tags as $tag) {
                $tag->updatePopularityScore();
                
                $isTrending = $tag->trending_score >= $trendingThreshold;
                
                if ($tag->is_trending !== $isTrending) {
                    $tag->update(['is_trending' => $isTrending]);
                }
            }
        });
        
        // Clear caches
        Cache::forget('trending_tags');
        Cache::forget('popular_tags');
    }

    // ===== BOOT METHOD =====

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
            
            // Auto-generate SEO fields
            if (empty($tag->seo_title)) {
                $tag->seo_title = $tag->name . ' - Articles and News';
            }
            
            if (empty($tag->seo_description)) {
                $tag->seo_description = "Explore articles tagged with {$tag->name}. " .
                    "Stay updated with the latest news and insights on {$tag->name}.";
            }
        });
        
        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
        
        static::saved(function (Tag $tag) {
            $tag->clearRelatedCaches();
        });
        
        static::deleted(function (Tag $tag) {
            $tag->clearRelatedCaches();
        });
    }
}