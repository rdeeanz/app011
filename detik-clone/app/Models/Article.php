<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Article Model - Advanced news article entity
 * 
 * Comprehensive news article management with:
 * - Advanced content management and structured data
 * - SEO optimization and meta management
 * - Multi-media support with cloud storage
 * - Engagement tracking and analytics
 * - AI-powered content analysis
 * - Editorial workflow and versioning
 * - Performance optimization with caching
 */
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Basic content
        'title',
        'slug',
        'excerpt',
        'content',
        'content_blocks',
        'content_format',
        'excerpt_auto',
        'word_count',
        'content_analysis',
        
        // Media
        'featured_image',
        'gallery',
        'media_gallery',
        'video_url',
        'featured_video',
        'embedded_media',
        'audio_file',
        'type',
        
        // SEO & Meta
        'meta_title',
        'meta_description',
        'meta_keywords',
        
        // Publication workflow
        'status',
        'editorial_status',
        'editorial_notes',
        'revision_history',
        'published_at',
        'submitted_at',
        'reviewed_at',
        'expires_at',
        
        // Relationships
        'author_id',
        'editor_id',
        'reviewer_id',
        'category_id',
        
        // Content relationships
        'related_articles',
        'internal_links',
        'external_links',
        'series_id',
        'series_order',
        
        // Engagement metrics
        'views_count',
        'unique_views',
        'shares_count',
        'social_shares',
        'comments_count',
        'engagement_rate',
        'time_spent_total',
        'avg_time_spent',
        'bounce_rate',
        'reading_time',
        
        // Flags and settings
        'is_featured',
        'is_breaking',
        'is_sponsored',
        'has_ads',
        'ad_config',
        'is_premium',
        'premium_price',
        'monetization_type',
        'comments_enabled',
        
        // Location and source
        'location',
        'source',
        
        // AI & Automation
        'ai_analysis',
        'auto_generated',
        'generation_model',
        'ai_confidence',
        'suggested_tags',
        'suggested_categories',
        
        // Performance
        'performance_metrics',
        'last_crawled_at',
        'seo_score',
        
        // Multi-language
        'language',
        'translations',
        'original_article_id',
    ];

    protected $casts = [
        // Content arrays and objects
        'content_blocks' => 'array',
        'content_analysis' => 'array',
        'gallery' => 'array',
        'media_gallery' => 'array',
        'embedded_media' => 'array',
        'meta_keywords' => 'array',
        'revision_history' => 'array',
        'related_articles' => 'array',
        'internal_links' => 'array',
        'external_links' => 'array',
        'social_shares' => 'array',
        'ad_config' => 'array',
        'ai_analysis' => 'array',
        'suggested_tags' => 'array',
        'suggested_categories' => 'array',
        'performance_metrics' => 'array',
        'seo_score' => 'array',
        'translations' => 'array',
        
        // Timestamps
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_crawled_at' => 'datetime',
        
        // Booleans
        'is_featured' => 'boolean',
        'is_breaking' => 'boolean',
        'is_sponsored' => 'boolean',
        'has_ads' => 'boolean',
        'is_premium' => 'boolean',
        'comments_enabled' => 'boolean',
        'auto_generated' => 'boolean',
        
        // Numbers
        'views_count' => 'integer',
        'unique_views' => 'integer',
        'shares_count' => 'integer',
        'comments_count' => 'integer',
        'word_count' => 'integer',
        'time_spent_total' => 'integer',
        'bounce_rate' => 'integer',
        'series_order' => 'integer',
        'reading_time' => 'decimal:1',
        'engagement_rate' => 'decimal:2',
        'avg_time_spent' => 'decimal:2',
        'premium_price' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
    ];

    protected $attributes = [
        'type' => 'article',
        'status' => 'draft',
        'views_count' => 0,
        'shares_count' => 0,
        'comments_count' => 0,
        'is_featured' => false,
        'is_breaking' => false,
        'is_sponsored' => false,
        'comments_enabled' => true,
    ];

    // ===== RELATIONSHIPS =====
    
    // User relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withDefault();
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Content relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps()
            ->withPivot(['tag_type', 'relevance_score', 'added_by', 'context']);
    }

    public function autoTags(): BelongsToMany
    {
        return $this->tags()->wherePivot('tag_type', 'auto');
    }

    public function manualTags(): BelongsToMany
    {
        return $this->tags()->wherePivot('tag_type', 'manual');
    }

    // Media relationships
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function featuredMedia(): MorphMany
    {
        return $this->media()->where('collection_name', 'featured');
    }

    public function galleryMedia(): MorphMany
    {
        return $this->media()->where('collection_name', 'gallery')->orderBy('order_column');
    }

    // Meta data relationship
    public function meta(): HasOne
    {
        return $this->hasOne(ArticleMeta::class);
    }

    // Comment relationships
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', 'approved');
    }

    public function topLevelComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    // Analytics relationships
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function analytics(): MorphMany
    {
        return $this->morphMany(Analytics::class, 'trackable');
    }

    public function uniqueViews(): HasMany
    {
        return $this->views()->where('is_unique_view', true);
    }

    // Activity log relationship
    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    // Bookmark relationship
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    // ===== QUERY SCOPES =====
    
    // Publication status scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('editorial_status', 'draft');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('editorial_status', 'pending_review');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
                    ->where('published_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    // Content type scopes
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_breaking', true);
    }

    public function scopeSponsored(Builder $query): Builder
    {
        return $query->where('is_sponsored', true);
    }

    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Category and taxonomy scopes
    public function scopeByCategory(Builder $query, int|Category $category): Builder
    {
        $categoryId = $category instanceof Category ? $category->id : $category;
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCategorySlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('category', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });
    }

    public function scopeByTag(Builder $query, int|string $tag): Builder
    {
        return $query->whereHas('tags', function ($q) use ($tag) {
            if (is_numeric($tag)) {
                $q->where('tags.id', $tag);
            } else {
                $q->where('tags.slug', $tag);
            }
        });
    }

    public function scopeByAuthor(Builder $query, int|User $author): Builder
    {
        $authorId = $author instanceof User ? $author->id : $author;
        return $query->where('author_id', $authorId);
    }

    // Time-based scopes
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('published_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('published_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('published_at', now()->month)
                    ->whereYear('published_at', now()->year);
    }

    // Engagement scopes
    public function scopePopular(Builder $query, ?int $limit = null): Builder
    {
        $query = $query->orderBy('views_count', 'desc')
                      ->orderBy('engagement_rate', 'desc');
        
        return $limit ? $query->limit($limit) : $query;
    }

    public function scopeTrending(Builder $query, int $hours = 24): Builder
    {
        return $query->where('published_at', '>=', now()->subHours($hours))
                    ->orderBy('views_count', 'desc')
                    ->orderBy('engagement_rate', 'desc');
    }

    public function scopeMostCommented(Builder $query): Builder
    {
        return $query->orderBy('comments_count', 'desc');
    }

    public function scopeMostShared(Builder $query): Builder
    {
        return $query->orderBy('shares_count', 'desc');
    }

    // Language and localization scopes
    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    public function scopeTranslations(Builder $query): Builder
    {
        return $query->whereNotNull('original_article_id');
    }

    public function scopeOriginals(Builder $query): Builder
    {
        return $query->whereNull('original_article_id');
    }

    // AI and automation scopes
    public function scopeAutoGenerated(Builder $query): Builder
    {
        return $query->where('auto_generated', true);
    }

    public function scopeHumanWritten(Builder $query): Builder
    {
        return $query->where('auto_generated', false);
    }

    // Search scope
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('excerpt', 'LIKE', "%{$term}%")
              ->orWhere('content', 'LIKE', "%{$term}%")
              ->orWhereHas('tags', function ($tagQuery) use ($term) {
                  $tagQuery->where('name', 'LIKE', "%{$term}%");
              });
        });
    }

    // Performance optimized scopes
    public function scopeWithEssentials(Builder $query): Builder
    {
        return $query->with(['author:id,name,avatar', 'category:id,name,slug,color']);
    }

    public function scopeForListing(Builder $query): Builder
    {
        return $query->select([
            'id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at',
            'author_id', 'category_id', 'views_count', 'comments_count',
            'reading_time', 'is_featured', 'is_breaking', 'is_sponsored'
        ])->withEssentials();
    }

    // Additional optimization scopes for ArticleRepository
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['comments', 'bookmarks', 'views']);
    }

    public function scopeMinimal(Builder $query): Builder
    {
        return $query->select(['id', 'title', 'slug', 'featured_image', 'published_at']);
    }

    public function scopeWithEngagement(Builder $query): Builder
    {
        return $query->addSelect([
            DB::raw('(views_count + comments_count * 2 + shares_count * 3) as engagement_score')
        ]);
    }

    public function scopeHighEngagement(Builder $query, int $threshold = 100): Builder
    {
        return $query->withEngagement()
            ->having('engagement_score', '>=', $threshold);
    }

    public function scopeForSitemap(Builder $query): Builder
    {
        return $query->select(['id', 'slug', 'updated_at'])
                    ->published()
                    ->orderBy('updated_at', 'desc');
    }

    // ===== ACCESSORS & MUTATORS =====
    
    // Title and slug handling
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = $value;
        
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        }
        
        // Auto-calculate reading time if content exists
        if (!empty($this->attributes['content'])) {
            $this->attributes['reading_time'] = $this->calculateReadingTime($this->attributes['content']);
        }
    }

    public function setContentAttribute(string $value): void
    {
        $this->attributes['content'] = $value;
        $this->attributes['word_count'] = str_word_count(strip_tags($value));
        $this->attributes['reading_time'] = $this->calculateReadingTime($value);
        
        // Auto-generate excerpt if not provided
        if (empty($this->attributes['excerpt'])) {
            $this->attributes['excerpt_auto'] = Str::limit(strip_tags($value), 160);
        }
    }

    // Enhanced excerpt handling
    public function getExcerptAttribute(?string $value): string
    {
        return $value ?: $this->excerpt_auto ?: Str::limit(strip_tags($this->content ?? ''), 160);
    }

    // SEO meta accessors
    public function getMetaTitleAttribute(?string $value): string
    {
        return $value ?: $this->title;
    }

    public function getMetaDescriptionAttribute(?string $value): string
    {
        return $value ?: $this->excerpt;
    }

    // URL and routing
    public function getUrlAttribute(): string
    {
        return route('articles.show', [
            'article' => $this->slug
        ]);
    }

    public function getPermalinkAttribute(): string
    {
        return $this->url;
    }

    // Media accessors
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return config('app.cdn_enabled') 
                ? config('app.cdn_url') . '/' . $this->featured_image
                : asset('storage/' . $this->featured_image);
        }
        return null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        // Return optimized thumbnail if available
        $media = $this->featuredMedia()->first();
        return $media?->getUrl('thumb') ?? $this->featured_image_url;
    }

    // Engagement metrics accessors
    public function getEngagementScoreAttribute(): float
    {
        if ($this->views_count === 0) return 0;
        
        $commentWeight = 3;
        $shareWeight = 2;
        $timeWeight = 1;
        
        $commentScore = ($this->comments_count / $this->views_count) * $commentWeight;
        $shareScore = ($this->shares_count / $this->views_count) * $shareWeight;
        $timeScore = ($this->avg_time_spent / max($this->reading_time * 60, 1)) * $timeWeight;
        
        return round(($commentScore + $shareScore + $timeScore) * 100, 2);
    }

    public function getReadabilityScoreAttribute(): ?float
    {
        return $this->content_analysis['readability_score'] ?? null;
    }

    // Status and workflow accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->editorial_status) {
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'in_review' => 'In Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'published' => 'Published',
            'archived' => 'Archived',
            default => 'Unknown'
        };
    }

    public function getPublicationStatusAttribute(): string
    {
        if ($this->isScheduled()) return 'scheduled';
        if ($this->isExpired()) return 'expired';
        if ($this->isPublished()) return 'published';
        return 'unpublished';
    }

    // Time and date accessors
    public function getPublishedAtHumanAttribute(): ?string
    {
        return $this->published_at?->diffForHumans();
    }

    public function getUpdatedAtHumanAttribute(): string
    {
        return $this->updated_at->diffForHumans();
    }

    // ===== HELPER METHODS =====
    
    // View tracking with caching
    public function incrementViews(bool $unique = false): void
    {
        // Hash IP for privacy compliance
        $hashedIp = hash('sha256', request()->ip() . config('app.key'));
        $cacheKey = "article_views_incremented_{$this->id}_{$hashedIp}_" . today()->format('Y-m-d');
        
        if ($unique && Cache::has($cacheKey)) {
            return; // Already counted today
        }
        
        DB::transaction(function () use ($unique) {
            $this->increment('views_count');
            if ($unique) {
                $this->increment('unique_views');
            }
        });
        
        if ($unique) {
            Cache::put($cacheKey, true, now()->endOfDay());
        }
        
        // Clear relevant caches
        $this->clearViewsCache();
    }

    public function incrementShares(?string $platform = null): void
    {
        $this->increment('shares_count');
        
        if ($platform) {
            $socialShares = $this->social_shares ?? [];
            $socialShares[$platform] = ($socialShares[$platform] ?? 0) + 1;
            $this->update(['social_shares' => $socialShares]);
        }
    }

    public function incrementEngagement(array $metrics): void
    {
        DB::transaction(function () use ($metrics) {
            if (isset($metrics['time_spent'])) {
                $this->increment('time_spent_total', $metrics['time_spent']);
                $this->updateAverageTimeSpent();
            }
            
            if (isset($metrics['scroll_depth'])) {
                $this->updateScrollMetrics($metrics['scroll_depth']);
            }
        });
    }

    // Status checking methods
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at && 
               $this->published_at->isPast() &&
               !$this->isExpired();
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && 
               $this->published_at && 
               $this->published_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isDraft(): bool
    {
        return $this->editorial_status === 'draft';
    }

    public function isPendingReview(): bool
    {
        return $this->editorial_status === 'pending_review';
    }

    public function canBePublished(): bool
    {
        return in_array($this->editorial_status, ['approved', 'published']);
    }

    public function isEditableBy(User $user): bool
    {
        if ($user->isAdmin() || $user->canPublish()) {
            return true;
        }
        
        return $this->author_id === $user->id && $this->isDraft();
    }

    // Content analysis methods
    public function analyzeContent(): array
    {
        $content = strip_tags($this->content);
        
        return [
            'word_count' => str_word_count($content),
            'character_count' => strlen($content),
            'paragraph_count' => count(explode("\n\n", $content)),
            'readability_score' => $this->calculateReadabilityScore($content),
            'keyword_density' => $this->calculateKeywordDensity($content),
            'internal_links_count' => $this->countInternalLinks(),
            'external_links_count' => $this->countExternalLinks(),
            'images_count' => $this->countImages(),
            'last_analyzed' => now(),
        ];
    }

    public function updateContentAnalysis(): void
    {
        $this->update(['content_analysis' => $this->analyzeContent()]);
    }

    // SEO methods
    public function generateSeoScore(): array
    {
        $score = 0;
        $recommendations = [];
        
        // Title length check
        $titleLength = strlen($this->title);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 10;
        } else {
            $recommendations[] = 'Title should be 30-60 characters long';
        }
        
        // Meta description check
        $metaLength = strlen($this->meta_description ?? '');
        if ($metaLength >= 120 && $metaLength <= 160) {
            $score += 10;
        } else {
            $recommendations[] = 'Meta description should be 120-160 characters long';
        }
        
        // Featured image check
        if ($this->featured_image) {
            $score += 10;
        } else {
            $recommendations[] = 'Add a featured image';
        }
        
        // Content length check
        if ($this->word_count >= 300) {
            $score += 10;
        } else {
            $recommendations[] = 'Content should be at least 300 words';
        }
        
        // Internal links check
        $internalLinks = $this->countInternalLinks();
        if ($internalLinks >= 2) {
            $score += 10;
        } else {
            $recommendations[] = 'Add at least 2 internal links';
        }
        
        return [
            'score' => $score,
            'max_score' => 50,
            'percentage' => round(($score / 50) * 100),
            'recommendations' => $recommendations,
            'last_calculated' => now(),
        ];
    }

    // Cache management
    public function clearViewsCache(): void
    {
        // Skip cache tagging for database cache driver
        if (config('cache.default') === 'database') {
            Cache::flush(); // Clear all cache for database driver
            return;
        }
        
        $tags = ["article:{$this->id}", "category:{$this->category_id}", 'popular_articles'];
        Cache::tags($tags)->flush();
    }

    public function clearAllCache(): void
    {
        // Skip cache tagging for database cache driver
        if (config('cache.default') === 'database') {
            Cache::flush(); // Clear all cache for database driver
            return;
        }
        
        $tags = [
            "article:{$this->id}",
            "category:{$this->category_id}",
            'popular_articles',
            'trending_articles',
            'featured_articles'
        ];
        Cache::tags($tags)->flush();
    }

    // Private helper methods
    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;
        
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        
        return $slug;
    }

    private function calculateReadingTime(string $content): float
    {
        $wordCount = str_word_count(strip_tags($content));
        return round($wordCount / 200, 1); // Assuming 200 WPM reading speed
    }

    private function updateAverageTimeSpent(): void
    {
        if ($this->unique_views > 0) {
            $this->avg_time_spent = round($this->time_spent_total / $this->unique_views, 2);
            $this->save();
        }
    }

    private function calculateReadabilityScore(string $content): float
    {
        // Simple Flesch Reading Ease approximation
        $sentences = preg_split('/[.!?]+/', $content);
        $words = str_word_count($content);
        $syllables = $this->countSyllables($content);
        
        if ($words === 0 || count($sentences) === 0) return 0;
        
        $avgWordsPerSentence = $words / count($sentences);
        $avgSyllablesPerWord = $syllables / $words;
        
        return 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
    }

    private function countSyllables(string $content): int
    {
        // Simple syllable counting approximation
        $words = str_word_count($content, 1);
        $syllables = 0;
        
        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouy]/i', $word));
        }
        
        return $syllables;
    }

    private function calculateKeywordDensity(string $content): array
    {
        $words = str_word_count(strtolower($content), 1);
        $totalWords = count($words);
        $wordCounts = array_count_values($words);
        
        // Filter out common words and short words
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $keywords = array_filter($wordCounts, function($word) use ($commonWords) {
            return strlen($word) > 3 && !in_array($word, $commonWords);
        }, ARRAY_FILTER_USE_KEY);
        
        arsort($keywords);
        
        $density = [];
        foreach (array_slice($keywords, 0, 10, true) as $word => $count) {
            $density[$word] = round(($count / $totalWords) * 100, 2);
        }
        
        return $density;
    }

    private function countInternalLinks(): int
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        return preg_match_all('/href=["\']https?:\/\/' . preg_quote($domain) . '/', $this->content ?? '');
    }

    private function countExternalLinks(): int
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        $totalLinks = preg_match_all('/href=["\']https?:\/\//', $this->content ?? '');
        $internalLinks = $this->countInternalLinks();
        return $totalLinks - $internalLinks;
    }

    private function countImages(): int
    {
        return preg_match_all('/<img\s+[^>]*>/i', $this->content ?? '');
    }

    // ===== UTILITY METHODS =====
    
    // Publish an article
    public function publish(?User $user = null): bool
    {
        if (!$this->canBePublished()) {
            return false;
        }
        
        return $this->update([
            'status' => 'published',
            'editorial_status' => 'published',
            'published_at' => now(),
            'published_by' => $user?->id,
        ]);
    }

    // Schedule for publication
    public function schedule(Carbon $publishAt, ?User $user = null): bool
    {
        if (!$this->canBePublished() || $publishAt->isPast()) {
            return false;
        }
        
        return $this->update([
            'status' => 'scheduled',
            'published_at' => $publishAt,
            'scheduled_by' => $user?->id,
        ]);
    }

    // Unpublish an article
    public function unpublish(?User $user = null): bool
    {
        return $this->update([
            'status' => 'draft',
            'editorial_status' => 'draft',
            'unpublished_at' => now(),
            'unpublished_by' => $user?->id,
        ]);
    }

    // Archive an article
    public function archive(?User $user = null): bool
    {
        return $this->update([
            'status' => 'archived',
            'editorial_status' => 'archived',
            'archived_at' => now(),
            'archived_by' => $user?->id,
        ]);
    }

    // Submit for review
    public function submitForReview(?User $user = null): bool
    {
        if (!$this->isDraft()) {
            return false;
        }
        
        return $this->update([
            'editorial_status' => 'pending_review',
            'submitted_for_review_at' => now(),
            'submitted_by' => $user?->id,
        ]);
    }

    // Approve article
    public function approve(?User $user = null): bool
    {
        if (!in_array($this->editorial_status, ['pending_review', 'in_review'])) {
            return false;
        }
        
        return $this->update([
            'editorial_status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user?->id,
        ]);
    }

    // Reject article
    public function reject(?User $user = null, ?string $reason = null): bool
    {
        if (!in_array($this->editorial_status, ['pending_review', 'in_review'])) {
            return false;
        }
        
        return $this->update([
            'editorial_status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user?->id,
            'rejection_reason' => $reason,
        ]);
    }

    // Feature/unfeature article
    public function feature(): bool
    {
        return $this->update(['is_featured' => true, 'featured_at' => now()]);
    }

    public function unfeature(): bool
    {
        return $this->update(['is_featured' => false, 'featured_at' => null]);
    }

    // Pin/unpin to top
    public function pin(): bool
    {
        return $this->update(['is_pinned' => true, 'pinned_at' => now()]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false, 'pinned_at' => null]);
    }

    // Enable/disable comments
    public function enableComments(): bool
    {
        return $this->update(['allow_comments' => true]);
    }

    public function disableComments(): bool
    {
        return $this->update(['allow_comments' => false]);
    }

    // Social sharing methods
    public function getShareUrl(string $platform): string
    {
        $url = urlencode($this->url);
        $title = urlencode($this->title);
        
        return match($platform) {
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
            'twitter' => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
            'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$url}",
            'whatsapp' => "https://wa.me/?text={$title}%20{$url}",
            'telegram' => "https://t.me/share/url?url={$url}&text={$title}",
            'email' => "mailto:?subject={$title}&body={$url}",
            default => $this->url,
        };
    }

    // JSON-LD structured data for SEO
    public function getStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $this->title,
            'description' => $this->excerpt,
            'image' => $this->featured_image_url,
            'author' => [
                '@type' => 'Person',
                'name' => $this->author->name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ]
            ],
            'datePublished' => $this->published_at?->toISOString(),
            'dateModified' => $this->updated_at->toISOString(),
            'articleSection' => $this->category->name,
            'keywords' => $this->tags->pluck('name')->join(', '),
            'url' => $this->url,
        ];
    }

    // Export article data
    public function export(string $format = 'json'): string
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $this->author->name,
            'category' => $this->category->name,
            'tags' => $this->tags->pluck('name')->toArray(),
            'published_at' => $this->published_at?->toISOString(),
            'meta' => [
                'views' => $this->views_count,
                'shares' => $this->shares_count,
                'comments' => $this->comments_count,
                'reading_time' => $this->reading_time,
                'word_count' => $this->word_count,
            ],
        ];
        
        return match($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'xml' => $this->arrayToXml($data),
            'csv' => $this->arrayToCsv($data),
            default => json_encode($data),
        };
    }

    // Duplicate article
    public function duplicate(?User $user = null): static
    {
        $duplicate = $this->replicate();
        $duplicate->title = $this->title . ' (Copy)';
        $duplicate->slug = null; // Will be auto-generated
        $duplicate->status = 'draft';
        $duplicate->editorial_status = 'draft';
        $duplicate->published_at = null;
        $duplicate->author_id = $user?->id ?? $this->author_id;
        $duplicate->views_count = 0;
        $duplicate->shares_count = 0;
        $duplicate->comments_count = 0;
        $duplicate->save();
        
        // Duplicate tags
        $duplicate->tags()->sync($this->tags->pluck('id'));
        
        return $duplicate;
    }

    // Related articles based on tags and category
    public function getRelatedArticles(int $limit = 5): Collection
    {
        $tagIds = $this->tags->pluck('id')->toArray();
        
        return static::published()
            ->where('id', '!=', $this->id)
            ->where(function ($query) use ($tagIds) {
                $query->where('category_id', $this->category_id)
                      ->orWhereHas('tags', function ($q) use ($tagIds) {
                          $q->whereIn('tags.id', $tagIds);
                      });
            })
            ->withCount('tags')
            ->orderByRaw('CASE WHEN category_id = ? THEN 1 ELSE 0 END DESC', [$this->category_id])
            ->orderBy('tags_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Performance monitoring
    public function trackPerformance(array $metrics): void
    {
        $performance = $this->performance_metrics ?? [];
        $performance[now()->format('Y-m-d H:i')] = $metrics;
        
        // Keep only last 24 hours of data
        $oneDayAgo = now()->subDay();
        $performance = array_filter($performance, function($timestamp) use ($oneDayAgo) {
            return Carbon::parse($timestamp)->isAfter($oneDayAgo);
        }, ARRAY_FILTER_USE_KEY);
        
        $this->update(['performance_metrics' => $performance]);
    }

    // Private utility methods
    private function arrayToXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<article/>');
        array_walk_recursive($data, [$xml, 'addChild']);
        return $xml->asXML();
    }

    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data));
        fputcsv($output, array_values($data));
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    // ===== EVENT HANDLERS =====
    
    protected static function booted(): void
    {
        // Auto-generate slug on creating
        static::creating(function (Article $article) {
            if (empty($article->slug)) {
                $article->slug = $article->generateUniqueSlug($article->title);
            }
        });

        // Clear cache on update
        static::updated(function (Article $article) {
            $article->clearAllCache();
        });

        // Clear cache on delete
        static::deleted(function (Article $article) {
            $article->clearAllCache();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
