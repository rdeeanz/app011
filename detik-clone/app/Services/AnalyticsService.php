<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnalyticsService
{
    protected int $cacheTtl = 3600; // 1 hour

    // ===== EVENT TRACKING =====

    /**
     * Track any event for any model
     */
    public function trackEvent(
        Model $trackable,
        string $eventType,
        array $eventData = [],
        ?User $user = null
    ): Analytics {
        try {
            return Analytics::track($trackable, $eventType, $eventData, $user);
        } catch (\Exception $e) {
            Log::error('Analytics tracking failed', [
                'trackable_type' => get_class($trackable),
                'trackable_id' => $trackable->id,
                'event_type' => $eventType,
                'error' => $e->getMessage()
            ]);
            
            // Return a dummy analytics object to prevent breaking the application
            return new Analytics();
        }
    }

    /**
     * Track article publication
     */
    public function trackArticlePublication(Article $article): void
    {
        $this->trackEvent($article, 'published', [
            'category_id' => $article->category_id,
            'author_id' => $article->author_id,
            'word_count' => $article->word_count,
            'reading_time' => $article->reading_time,
            'is_featured' => $article->is_featured,
        ]);
    }

    /**
     * Track user engagement
     */
    public function trackUserEngagement(User $user, array $engagementData): void
    {
        $this->trackEvent($user, 'engagement', $engagementData, $user);
    }

    // ===== PERFORMANCE ANALYTICS =====

    /**
     * Get comprehensive site analytics
     */
    public function getSiteAnalytics(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();
        
        $cacheKey = "site_analytics:{$from->format('Y-m-d')}:{$to->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($from, $to) {
            return [
                'overview' => $this->getOverviewMetrics($from, $to),
                'traffic' => $this->getTrafficMetrics($from, $to),
                'content' => $this->getContentMetrics($from, $to),
                'user_behavior' => $this->getUserBehaviorMetrics($from, $to),
                'devices' => $this->getDeviceMetrics($from, $to),
                'geography' => $this->getGeographyMetrics($from, $to),
                'trends' => $this->getTrendMetrics($from, $to),
            ];
        });
    }

    /**
     * Get article performance analytics
     */
    public function getArticleAnalytics(Article $article, Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? $article->published_at ?? now()->subDays(30);
        $to = $to ?? now();
        
        $cacheKey = "article_analytics:{$article->id}:{$from->format('Y-m-d')}:{$to->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, $this->cacheTtl / 2, function () use ($article, $from, $to) {
            return [
                'basic_metrics' => $this->getArticleBasicMetrics($article),
                'engagement' => $this->getArticleEngagementMetrics($article, $from, $to),
                'traffic_sources' => $this->getArticleTrafficSources($article, $from, $to),
                'social_shares' => $this->getArticleSocialShares($article),
                'reading_behavior' => $this->getArticleReadingBehavior($article, $from, $to),
                'demographics' => $this->getArticleDemographics($article, $from, $to),
                'performance_history' => $this->getArticlePerformanceHistory($article, $from, $to),
            ];
        });
    }

    /**
     * Get category performance
     */
    public function getCategoryAnalytics(Category $category, Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();
        
        return [
            'articles_count' => $category->articles()->published()->count(),
            'total_views' => $this->getCategoryViews($category, $from, $to),
            'average_engagement' => $this->getCategoryAverageEngagement($category, $from, $to),
            'top_articles' => $this->getCategoryTopArticles($category, $from, $to),
            'growth_trend' => $this->getCategoryGrowthTrend($category, $from, $to),
        ];
    }

    // ===== REPORTING METHODS =====

    /**
     * Generate daily report
     */
    public function generateDailyReport(Carbon $date = null): array
    {
        $date = $date ?? now()->subDay();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();
        
        return [
            'date' => $date->format('Y-m-d'),
            'total_views' => $this->getTotalViews($from, $to),
            'unique_visitors' => $this->getUniqueVisitors($from, $to),
            'articles_published' => $this->getArticlesPublished($from, $to),
            'top_articles' => $this->getTopArticles($from, $to, 10),
            'traffic_sources' => $this->getTrafficSources($from, $to),
            'device_breakdown' => $this->getDeviceBreakdown($from, $to),
            'engagement_metrics' => $this->getEngagementMetrics($from, $to),
        ];
    }

    /**
     * Generate weekly report
     */
    public function generateWeeklyReport(Carbon $weekStart = null): array
    {
        $weekStart = $weekStart ?? now()->startOfWeek()->subWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        return [
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'overview' => $this->getSiteAnalytics($weekStart, $weekEnd)['overview'],
            'top_performing_content' => $this->getTopPerformingContent($weekStart, $weekEnd),
            'author_performance' => $this->getAuthorPerformance($weekStart, $weekEnd),
            'category_performance' => $this->getCategoryPerformanceReport($weekStart, $weekEnd),
            'growth_metrics' => $this->getGrowthMetrics($weekStart, $weekEnd),
        ];
    }

    /**
     * Generate monthly report
     */
    public function generateMonthlyReport(Carbon $month = null): array
    {
        $month = $month ?? now()->subMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        
        return [
            'month' => $month->format('Y-m'),
            'comprehensive_analytics' => $this->getSiteAnalytics($from, $to),
            'content_analysis' => $this->getContentAnalysis($from, $to),
            'user_retention' => $this->getUserRetentionMetrics($from, $to),
            'revenue_metrics' => $this->getRevenueMetrics($from, $to),
            'goals_progress' => $this->getGoalsProgress($from, $to),
            'recommendations' => $this->generateRecommendations($from, $to),
        ];
    }

    // ===== INDIVIDUAL METRIC METHODS =====

    protected function getOverviewMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'total_views' => $this->getTotalViews($from, $to),
            'unique_visitors' => $this->getUniqueVisitors($from, $to),
            'page_views' => $this->getPageViews($from, $to),
            'bounce_rate' => $this->getBounceRate($from, $to),
            'average_session_duration' => $this->getAverageSessionDuration($from, $to),
            'new_vs_returning' => $this->getNewVsReturningVisitors($from, $to),
        ];
    }

    protected function getTrafficMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'sources' => $this->getTrafficSources($from, $to),
            'search_keywords' => $this->getSearchKeywords($from, $to),
            'referrers' => $this->getTopReferrers($from, $to),
            'social_traffic' => $this->getSocialTraffic($from, $to),
        ];
    }

    protected function getContentMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'top_articles' => $this->getTopArticles($from, $to),
            'top_categories' => $this->getTopCategories($from, $to),
            'content_engagement' => $this->getContentEngagementMetrics($from, $to),
            'reading_behavior' => $this->getReadingBehaviorMetrics($from, $to),
        ];
    }

    protected function getUserBehaviorMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'user_flow' => $this->getUserFlow($from, $to),
            'exit_pages' => $this->getExitPages($from, $to),
            'time_on_site' => $this->getTimeOnSite($from, $to),
            'pages_per_session' => $this->getPagesPerSession($from, $to),
        ];
    }

    protected function getDeviceMetrics(Carbon $from, Carbon $to): array
    {
        return Analytics::dateRange($from, $to)
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();
    }

    protected function getGeographyMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'countries' => Analytics::dateRange($from, $to)
                ->selectRaw('country, COUNT(*) as visits')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('visits', 'desc')
                ->limit(20)
                ->pluck('visits', 'country')
                ->toArray(),
            'cities' => Analytics::dateRange($from, $to)
                ->selectRaw('city, COUNT(*) as visits')
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderBy('visits', 'desc')
                ->limit(20)
                ->pluck('visits', 'city')
                ->toArray(),
        ];
    }

    protected function getTrendMetrics(Carbon $from, Carbon $to): array
    {
        $daily = Analytics::dateRange($from, $to)
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as events')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('events', 'date')
            ->toArray();
        
        return [
            'daily_trends' => $daily,
            'growth_rate' => $this->calculateGrowthRate($daily),
            'seasonal_patterns' => $this->detectSeasonalPatterns($daily),
        ];
    }

    protected function getTotalViews(Carbon $from, Carbon $to): int
    {
        return Analytics::views()
            ->dateRange($from, $to)
            ->count();
    }

    protected function getUniqueVisitors(Carbon $from, Carbon $to): int
    {
        return Analytics::dateRange($from, $to)
            ->distinct('ip_address')
            ->count('ip_address');
    }

    protected function getPageViews(Carbon $from, Carbon $to): int
    {
        return Analytics::views()
            ->dateRange($from, $to)
            ->count();
    }

    protected function getBounceRate(Carbon $from, Carbon $to): float
    {
        // Simplified bounce rate calculation
        $totalSessions = Analytics::dateRange($from, $to)
            ->distinct('session_id')
            ->count();
        
        if ($totalSessions === 0) return 0;
        
        $singlePageSessions = Analytics::dateRange($from, $to)
            ->selectRaw('session_id, COUNT(*) as page_views')
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();
        
        return round(($singlePageSessions / $totalSessions) * 100, 2);
    }

    protected function getTopArticles(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Analytics::views()
            ->dateRange($from, $to)
            ->where('trackable_type', Article::class)
            ->selectRaw('trackable_id, COUNT(*) as views')
            ->groupBy('trackable_id')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $article = Article::find($item->trackable_id);
                return [
                    'article' => $article,
                    'views' => $item->views,
                    'title' => $article->title ?? 'Unknown',
                    'url' => $article->url ?? '#',
                ];
            })
            ->toArray();
    }

    protected function getTrafficSources(Carbon $from, Carbon $to): array
    {
        return Analytics::dateRange($from, $to)
            ->selectRaw('
                CASE 
                    WHEN referrer IS NULL OR referrer = "" THEN "Direct"
                    WHEN referrer LIKE "%google%" THEN "Google"
                    WHEN referrer LIKE "%facebook%" THEN "Facebook"
                    WHEN referrer LIKE "%twitter%" THEN "Twitter"
                    WHEN referrer LIKE "%instagram%" THEN "Instagram"
                    ELSE "Other"
                END as source,
                COUNT(*) as visits
            ')
            ->groupBy('source')
            ->orderBy('visits', 'desc')
            ->pluck('visits', 'source')
            ->toArray();
    }

    // ===== UTILITY METHODS =====

    protected function calculateGrowthRate(array $dailyData): float
    {
        if (count($dailyData) < 2) return 0;
        
        $values = array_values($dailyData);
        $firstValue = reset($values);
        $lastValue = end($values);
        
        if ($firstValue === 0) return 0;
        
        return round((($lastValue - $firstValue) / $firstValue) * 100, 2);
    }

    protected function detectSeasonalPatterns(array $dailyData): array
    {
        // Simple seasonal pattern detection
        $dayOfWeekPattern = [];
        
        foreach ($dailyData as $date => $value) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            $dayOfWeekPattern[$dayOfWeek] = ($dayOfWeekPattern[$dayOfWeek] ?? 0) + $value;
        }
        
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $namedPattern = [];
        
        foreach ($dayOfWeekPattern as $day => $value) {
            $namedPattern[$dayNames[$day]] = $value;
        }
        
        return $namedPattern;
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        Cache::tags(['analytics'])->flush();
    }

    /**
     * Get real-time analytics
     */
    public function getRealTimeAnalytics(): array
    {
        $now = now();
        $lastHour = $now->copy()->subHour();
        
        return [
            'active_users' => $this->getActiveUsers($lastHour, $now),
            'current_popular_articles' => $this->getTopArticles($lastHour, $now, 5),
            'recent_events' => $this->getRecentEvents(50),
            'live_metrics' => [
                'views_last_hour' => $this->getTotalViews($lastHour, $now),
                'shares_last_hour' => $this->getTotalShares($lastHour, $now),
                'comments_last_hour' => $this->getTotalComments($lastHour, $now),
            ],
        ];
    }

    protected function getActiveUsers(Carbon $from, Carbon $to): int
    {
        return Analytics::dateRange($from, $to)
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->count();
    }

    protected function getRecentEvents(int $limit): array
    {
        return Analytics::orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'occurred_at' => $event->occurred_at,
                    'trackable_type' => class_basename($event->trackable_type),
                    'user_id' => $event->user_id,
                ];
            })
            ->toArray();
    }

    protected function getTotalShares(Carbon $from, Carbon $to): int
    {
        return Analytics::shares()
            ->dateRange($from, $to)
            ->count();
    }

    protected function getTotalComments(Carbon $from, Carbon $to): int
    {
        return Analytics::comments()
            ->dateRange($from, $to)
            ->count();
    }

    // ===== ARTICLE-SPECIFIC ANALYTICS =====

    protected function getArticleBasicMetrics(Article $article): array
    {
        return [
            'total_views' => $article->views_count,
            'unique_views' => $article->unique_views,
            'shares' => $article->shares_count,
            'comments' => $article->comments_count,
            'bookmarks' => $article->bookmarks_count,
            'engagement_score' => $article->engagement_score,
            'reading_time' => $article->reading_time,
            'word_count' => $article->word_count,
        ];
    }

    protected function getArticleEngagementMetrics(Article $article, Carbon $from, Carbon $to): array
    {
        return [
            'views_trend' => $this->getArticleViewsTrend($article, $from, $to),
            'engagement_rate' => $this->calculateArticleEngagementRate($article),
            'time_spent_reading' => $article->avg_time_spent,
            'scroll_depth' => $article->avg_scroll_depth,
            'social_engagement' => $article->social_shares ?? [],
        ];
    }

    protected function getArticleViewsTrend(Article $article, Carbon $from, Carbon $to): array
    {
        return Analytics::views()
            ->where('trackable_type', Article::class)
            ->where('trackable_id', $article->id)
            ->dateRange($from, $to)
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('views', 'date')
            ->toArray();
    }

    protected function calculateArticleEngagementRate(Article $article): float
    {
        if ($article->views_count === 0) return 0;
        
        $engagements = $article->shares_count + $article->comments_count + $article->bookmarks_count;
        return round(($engagements / $article->views_count) * 100, 2);
    }
}