<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Repositories\ArticleRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ArticleService
{
    protected ArticleRepository $articleRepository;
    protected MediaService $mediaService;
    protected AnalyticsService $analyticsService;
    protected NotificationService $notificationService;

    public function __construct(
        ArticleRepository $articleRepository,
        MediaService $mediaService,
        AnalyticsService $analyticsService,
        NotificationService $notificationService
    ) {
        $this->articleRepository = $articleRepository;
        $this->mediaService = $mediaService;
        $this->analyticsService = $analyticsService;
        $this->notificationService = $notificationService;
    }

    // ===== ARTICLE CREATION & MANAGEMENT =====

    /**
     * Create a new article with all associated data
     */
    public function createArticle(array $data, User $author): Article
    {
        DB::beginTransaction();
        
        try {
            // Prepare article data
            $articleData = [
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? null,
                'author_id' => $author->id,
                'category_id' => $data['category_id'],
                'status' => $data['status'] ?? 'draft',
                'editorial_status' => $data['editorial_status'] ?? 'draft',
                'is_featured' => $data['is_featured'] ?? false,
                'allow_comments' => $data['allow_comments'] ?? true,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'published_at' => isset($data['published_at']) ? Carbon::parse($data['published_at']) : null,
            ];

            // Create the article
            $article = $this->articleRepository->create($articleData);

            // Handle featured image
            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                $featuredImage = $this->mediaService->uploadImage(
                    $data['featured_image'],
                    $article,
                    'featured'
                );
                $article->update(['featured_image' => $featuredImage->path]);
            }

            // Handle additional media
            if (isset($data['media']) && is_array($data['media'])) {
                foreach ($data['media'] as $mediaFile) {
                    if ($mediaFile instanceof UploadedFile) {
                        $this->mediaService->uploadFile($mediaFile, $article);
                    }
                }
            }

            // Handle tags
            if (isset($data['tags'])) {
                $this->syncTags($article, $data['tags'], $author);
            }

            // Handle metadata
            if (isset($data['meta']) && is_array($data['meta'])) {
                foreach ($data['meta'] as $key => $value) {
                    $article->meta()->create([
                        'meta_key' => $key,
                        'meta_value' => $value,
                        'meta_type' => $this->detectMetaType($value),
                    ]);
                }
            }

            // Analyze content
            $this->analyzeArticleContent($article);

            // Log activity
            $this->logActivity($article, 'created', $author);

            // Auto-publish if requested and user has permission
            if (($data['status'] ?? '') === 'published' && $author->canPublish()) {
                $this->publishArticle($article, $author);
            }

            DB::commit();

            return $article->fresh(['author', 'category', 'tags', 'media']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Article creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'author_id' => $author->id
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing article
     */
    public function updateArticle(Article $article, array $data, User $user): Article
    {
        DB::beginTransaction();
        
        try {
            $oldData = $article->toArray();

            // Update article data
            $updateData = array_filter([
                'title' => $data['title'] ?? null,
                'content' => $data['content'] ?? null,
                'excerpt' => $data['excerpt'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'is_featured' => $data['is_featured'] ?? null,
                'allow_comments' => $data['allow_comments'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ], fn($value) => $value !== null);

            if (!empty($updateData)) {
                $article = $this->articleRepository->update($article, $updateData);
            }

            // Handle featured image update
            if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
                // Delete old featured image
                if ($article->featured_image) {
                    $this->mediaService->deleteFile($article->featured_image);
                }
                
                $featuredImage = $this->mediaService->uploadImage(
                    $data['featured_image'],
                    $article,
                    'featured'
                );
                $article->update(['featured_image' => $featuredImage->path]);
            }

            // Handle tags update
            if (isset($data['tags'])) {
                $this->syncTags($article, $data['tags'], $user);
            }

            // Re-analyze content if content changed
            if (isset($data['content'])) {
                $this->analyzeArticleContent($article);
            }

            // Log activity with changes
            $changes = array_diff_assoc($article->toArray(), $oldData);
            $this->logActivity($article, 'updated', $user, ['changes' => $changes]);

            DB::commit();

            return $article->fresh(['author', 'category', 'tags', 'media']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Article update failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => $user->id
            ]);
            throw $e;
        }
    }

    // ===== PUBLICATION WORKFLOW =====

    /**
     * Submit article for review
     */
    public function submitForReview(Article $article, User $author): bool
    {
        if (!$article->isDraft()) {
            throw new \InvalidArgumentException('Only draft articles can be submitted for review');
        }

        $success = $article->submitForReview($author);
        
        if ($success) {
            $this->logActivity($article, 'submitted_for_review', $author);
            $this->notificationService->notifyEditorsOfSubmission($article);
        }
        
        return $success;
    }

    /**
     * Approve an article
     */
    public function approveArticle(Article $article, User $reviewer): bool
    {
        if (!$reviewer->canReview()) {
            throw new \UnauthorizedException('User does not have review permissions');
        }

        $success = $article->approve($reviewer);
        
        if ($success) {
            $this->logActivity($article, 'approved', $reviewer);
            $this->notificationService->notifyAuthorOfApproval($article);
        }
        
        return $success;
    }

    /**
     * Reject an article
     */
    public function rejectArticle(Article $article, User $reviewer, string $reason = null): bool
    {
        if (!$reviewer->canReview()) {
            throw new \UnauthorizedException('User does not have review permissions');
        }

        $success = $article->reject($reviewer, $reason);
        
        if ($success) {
            $this->logActivity($article, 'rejected', $reviewer, ['reason' => $reason]);
            $this->notificationService->notifyAuthorOfRejection($article, $reason);
        }
        
        return $success;
    }

    /**
     * Publish an article
     */
    public function publishArticle(Article $article, User $publisher): bool
    {
        if (!$publisher->canPublish()) {
            throw new \UnauthorizedException('User does not have publish permissions');
        }

        $success = $article->publish($publisher);
        
        if ($success) {
            $this->logActivity($article, 'published', $publisher);
            $this->notificationService->notifySubscribersOfNewArticle($article);
            $this->analyticsService->trackArticlePublication($article);
            
            // Clear relevant caches
            $this->clearArticleCache($article);
        }
        
        return $success;
    }

    /**
     * Schedule article for publication
     */
    public function scheduleArticle(Article $article, Carbon $publishAt, User $scheduler): bool
    {
        if (!$scheduler->canPublish()) {
            throw new \UnauthorizedException('User does not have publish permissions');
        }

        $success = $article->schedule($publishAt, $scheduler);
        
        if ($success) {
            $this->logActivity($article, 'scheduled', $scheduler, [
                'scheduled_for' => $publishAt->toISOString()
            ]);
        }
        
        return $success;
    }

    /**
     * Unpublish an article
     */
    public function unpublishArticle(Article $article, User $user): bool
    {
        if (!$user->canPublish()) {
            throw new \UnauthorizedException('User does not have publish permissions');
        }

        $success = $article->unpublish($user);
        
        if ($success) {
            $this->logActivity($article, 'unpublished', $user);
            $this->clearArticleCache($article);
        }
        
        return $success;
    }

    // ===== CONTENT MANAGEMENT =====

    /**
     * Feature or unfeature an article
     */
    public function toggleFeatured(Article $article, User $user): bool
    {
        if (!$user->canFeature()) {
            throw new \UnauthorizedException('User cannot feature articles');
        }

        $action = $article->is_featured ? 'unfeatured' : 'featured';
        $success = $article->is_featured ? $article->unfeature() : $article->feature();
        
        if ($success) {
            $this->logActivity($article, $action, $user);
            $this->clearArticleCache($article);
        }
        
        return $success;
    }

    /**
     * Pin or unpin an article
     */
    public function togglePinned(Article $article, User $user): bool
    {
        if (!$user->canPin()) {
            throw new \UnauthorizedException('User cannot pin articles');
        }

        $action = $article->is_pinned ? 'unpinned' : 'pinned';
        $success = $article->is_pinned ? $article->unpin() : $article->pin();
        
        if ($success) {
            $this->logActivity($article, $action, $user);
            $this->clearArticleCache($article);
        }
        
        return $success;
    }

    /**
     * Archive an article
     */
    public function archiveArticle(Article $article, User $user): bool
    {
        if (!$user->canArchive()) {
            throw new \UnauthorizedException('User cannot archive articles');
        }

        $success = $article->archive($user);
        
        if ($success) {
            $this->logActivity($article, 'archived', $user);
            $this->clearArticleCache($article);
        }
        
        return $success;
    }

    /**
     * Duplicate an article
     */
    public function duplicateArticle(Article $article, User $user, array $changes = []): Article
    {
        DB::beginTransaction();
        
        try {
            $duplicate = $article->duplicate($user);
            
            // Apply any changes
            if (!empty($changes)) {
                $duplicate->update($changes);
            }
            
            $this->logActivity($duplicate, 'duplicated', $user, [
                'original_article_id' => $article->id
            ]);
            
            DB::commit();
            
            return $duplicate;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ===== CONTENT ANALYSIS =====

    /**
     * Analyze article content for SEO and readability
     */
    public function analyzeArticleContent(Article $article): array
    {
        $analysis = $article->analyzeContent();
        
        // Update article with analysis results
        $article->update(['content_analysis' => $analysis]);
        
        // Generate SEO recommendations
        $seoScore = $article->generateSeoScore();
        
        return [
            'content_analysis' => $analysis,
            'seo_score' => $seoScore,
        ];
    }

    /**
     * Generate article suggestions based on content
     */
    public function generateArticleSuggestions(Article $article): array
    {
        return [
            'related_articles' => $this->articleRepository->getRelated($article, 10),
            'tag_suggestions' => $this->suggestTags($article),
            'category_suggestions' => $this->suggestCategories($article),
            'title_improvements' => $this->suggestTitleImprovements($article),
            'content_improvements' => $this->suggestContentImprovements($article),
        ];
    }

    // ===== ENGAGEMENT TRACKING =====

    /**
     * Track article view
     */
    public function trackView(Article $article, User $user = null): void
    {
        $article->incrementViews(true);
        
        $this->analyticsService->trackEvent($article, 'view', [
            'user_id' => $user?->id,
            'timestamp' => now(),
            'referrer' => request()->header('referer'),
        ], $user);
    }

    /**
     * Track article share
     */
    public function trackShare(Article $article, string $platform, User $user = null): void
    {
        $article->incrementShares($platform);
        
        $this->analyticsService->trackEvent($article, 'share', [
            'platform' => $platform,
            'user_id' => $user?->id,
            'timestamp' => now(),
        ], $user);
    }

    // ===== UTILITY METHODS =====

    /**
     * Sync tags with an article
     */
    protected function syncTags(Article $article, array $tags, User $user): void
    {
        $tagIds = [];
        
        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagIds[$tag] = ['added_by' => $user->id, 'tag_type' => 'manual'];
            } elseif (is_string($tag)) {
                $tagModel = Tag::firstOrCreate(
                    ['name' => $tag],
                    ['slug' => Str::slug($tag)]
                );
                $tagIds[$tagModel->id] = ['added_by' => $user->id, 'tag_type' => 'manual'];
            }
        }
        
        $article->tags()->sync($tagIds);
    }

    /**
     * Detect meta value type
     */
    protected function detectMetaType(mixed $value): string
    {
        return match(true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    /**
     * Log article activity
     */
    protected function logActivity(Article $article, string $action, User $user, array $properties = []): void
    {
        \App\Models\ActivityLog::logArticleAction($article, $action, $user, $properties);
    }

    /**
     * Clear article-related cache
     */
    protected function clearArticleCache(Article $article): void
    {
        try {
            Cache::tags([
                'articles',
                "article:{$article->id}",
                "category:{$article->category_id}",
                'featured',
                'popular',
                'trending'
            ])->flush();
        } catch (\Exception $e) {
            // Cache driver may not support tags
            Log::warning('Cache clearing failed: ' . $e->getMessage());
        }
    }

    // ===== AI-POWERED SUGGESTIONS =====

    /**
     * Suggest tags based on content analysis
     */
    protected function suggestTags(Article $article): array
    {
        $analysis = $article->content_analysis ?? [];
        $keywordDensity = $analysis['keyword_density'] ?? [];
        
        $suggestions = [];
        foreach ($keywordDensity as $keyword => $density) {
            if ($density > 1.0) { // Keywords appearing more than 1% of the time
                $suggestions[] = [
                    'name' => ucfirst($keyword),
                    'relevance' => min($density / 5.0, 1.0), // Cap at 1.0
                    'type' => 'content-based'
                ];
            }
        }
        
        return array_slice($suggestions, 0, 10);
    }

    /**
     * Suggest categories based on content
     */
    protected function suggestCategories(Article $article): array
    {
        // Simple keyword-based category suggestion
        $content = strtolower($article->content);
        $suggestions = [];
        
        $categoryKeywords = [
            'Technology' => ['tech', 'software', 'app', 'digital', 'computer', 'internet'],
            'Sports' => ['football', 'soccer', 'basketball', 'sports', 'game', 'match'],
            'Politics' => ['government', 'election', 'president', 'minister', 'policy', 'law'],
            'Business' => ['economy', 'market', 'company', 'business', 'finance', 'stock'],
            'Entertainment' => ['movie', 'music', 'celebrity', 'artist', 'film', 'show'],
        ];
        
        foreach ($categoryKeywords as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($content, $keyword);
            }
            
            if ($score > 0) {
                $suggestions[] = [
                    'name' => $category,
                    'score' => $score,
                    'relevance' => min($score / 10.0, 1.0)
                ];
            }
        }
        
        // Sort by score in descending order and take top 5
        usort($suggestions, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($suggestions, 0, 5);
    }

    /**
     * Suggest title improvements
     */
    protected function suggestTitleImprovements(Article $article): array
    {
        $suggestions = [];
        $title = $article->title;
        $titleLength = strlen($title);
        
        if ($titleLength < 30) {
            $suggestions[] = [
                'type' => 'length',
                'message' => 'Consider making your title longer (30-60 characters) for better SEO',
                'current' => $titleLength,
                'recommended' => '30-60'
            ];
        } elseif ($titleLength > 60) {
            $suggestions[] = [
                'type' => 'length',
                'message' => 'Consider shortening your title (30-60 characters) for better SEO',
                'current' => $titleLength,
                'recommended' => '30-60'
            ];
        }
        
        // Check for power words
        $powerWords = ['ultimate', 'complete', 'essential', 'proven', 'powerful', 'effective'];
        $hasPowerWords = false;
        foreach ($powerWords as $word) {
            if (stripos($title, $word) !== false) {
                $hasPowerWords = true;
                break;
            }
        }
        
        if (!$hasPowerWords) {
            $suggestions[] = [
                'type' => 'engagement',
                'message' => 'Consider adding power words to make your title more engaging',
                'examples' => array_slice($powerWords, 0, 3)
            ];
        }
        
        return $suggestions;
    }

    /**
     * Suggest content improvements
     */
    protected function suggestContentImprovements(Article $article): array
    {
        $suggestions = [];
        $analysis = $article->content_analysis ?? [];
        
        // Word count suggestions
        $wordCount = $analysis['word_count'] ?? 0;
        if ($wordCount < 300) {
            $suggestions[] = [
                'type' => 'length',
                'message' => 'Content is quite short. Consider expanding to at least 300 words for better SEO.',
                'current' => $wordCount,
                'recommended' => 300
            ];
        }
        
        // Readability suggestions
        $readabilityScore = $analysis['readability_score'] ?? 0;
        if ($readabilityScore < 60) {
            $suggestions[] = [
                'type' => 'readability',
                'message' => 'Content readability could be improved. Try shorter sentences and simpler words.',
                'current' => round($readabilityScore, 1),
                'target' => '60+'
            ];
        }
        
        // Link suggestions
        $internalLinks = $analysis['internal_links_count'] ?? 0;
        if ($internalLinks < 2) {
            $suggestions[] = [
                'type' => 'seo',
                'message' => 'Add more internal links to improve SEO and user engagement.',
                'current' => $internalLinks,
                'recommended' => '2-5'
            ];
        }
        
        return $suggestions;
    }
}