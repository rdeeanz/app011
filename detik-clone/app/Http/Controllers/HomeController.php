<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Services\ArticleService;
use App\Services\AnalyticsService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * HomeController - Main News Portal Homepage Controller
 * 
 * Features:
 * - Homepage with featured articles and breaking news
 * - Dynamic content loading and infinite scroll
 * - Category-based article sections
 * - Trending topics and popular articles
 * - Search functionality with autocomplete
 * - Performance optimized with caching
 */
class HomeController extends Controller
{
    public function __construct(
        private ArticleService $articleService,
        private AnalyticsService $analyticsService,
        private CacheService $cacheService
    ) {}

    /**
     * Display the news portal homepage
     */
    public function index(): View
    {
        // Get cached data for better performance
        $data = $this->cacheService->remember('homepage_data', 300, function () {
            return [
                'breakingNews' => $this->getBreakingNews(),
                'featuredArticles' => $this->getFeaturedArticles(),
                'latestNews' => $this->getLatestNews(),
                'popularArticles' => $this->getPopularArticles(),
                'trendingTopics' => $this->getTrendingTopics(),
                'categories' => $this->getMainCategories(),
                'editorsPicks' => $this->getEditorsPicks(),
            ];
        });

        // Track homepage view
        $this->analyticsService->trackEvent('homepage_view', [
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);

        return view('home.index', $data);
    }

    /**
     * Load more articles for infinite scroll
     */
    public function loadMore(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'category' => 'nullable|string',
            'type' => 'nullable|string|in:latest,popular,trending',
        ]);

        $page = $request->input('page', 1);
        $category = $request->input('category');
        $type = $request->input('type', 'latest');
        $perPage = 10;

        $articles = $this->getArticlesByType($type, $category, $page, $perPage);

        return response()->json([
            'success' => true,
            'articles' => $articles->items(),
            'hasMore' => $articles->hasMorePages(),
            'nextPage' => $articles->currentPage() + 1,
            'total' => $articles->total(),
        ]);
    }

    /**
     * Global search functionality
     */
    public function search(Request $request): View|JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'category' => 'nullable|string',
            'tag' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'sort' => 'nullable|string|in:relevance,date,popularity',
            'ajax' => 'nullable|boolean',
        ]);

        $query = $request->input('q');
        $filters = [
            'category' => $request->input('category'),
            'tag' => $request->input('tag'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'sort' => $request->input('sort', 'relevance'),
        ];

        // Track search query with consent check
        if (config('app.collect_search_analytics', false) && session('analytics_consent', false)) {
            $trackingData = [
                'query' => $query,
                'filters' => array_filter($filters),
            ];
            
            // Only include user data with consent
            if (auth()->check() && session('user_analytics_consent', false)) {
                $trackingData['user_hash'] = hash('sha256', auth()->id() . config('app.key'));
            }
            
            // Anonymize IP address
            $ip = request()->ip();
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $trackingData['ip_hash'] = hash('sha256', preg_replace('/\.\d+$/', '.0', $ip));
            }
            
            $this->analyticsService->trackEvent('search_query', $trackingData);
        }

        $results = $this->articleService->searchArticles($query, $filters, 20);
        $suggestions = $this->getSearchSuggestions($query);

        if ($request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'results' => $results->items(),
                'suggestions' => $suggestions,
                'total' => $results->total(),
                'query' => $query,
                'filters' => $filters,
            ]);
        }

        return view('search.results', [
            'results' => $results,
            'suggestions' => $suggestions,
            'query' => $query,
            'filters' => $filters,
            'categories' => Category::active()->orderBy('name')->get(),
            'popularTags' => Tag::popular()->limit(20)->get(),
        ]);
    }

    /**
     * Get search suggestions for autocomplete
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:50',
        ]);

        $query = $request->input('q');
        $suggestions = $this->getSearchSuggestions($query, 10);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'query' => $query,
        ]);
    }

    /**
     * Get trending topics
     */
    public function trending(): JsonResponse
    {
        $trending = $this->cacheService->remember('trending_topics', 900, function () {
            return [
                'tags' => Tag::trending()->limit(10)->get(),
                'articles' => Article::trending()->limit(10)->get(),
                'categories' => Category::popular()->limit(5)->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'trending' => $trending,
        ]);
    }

    /**
     * Get newsletter subscription data
     */
    public function newsletter(): View
    {
        $stats = [
            'totalArticles' => Article::published()->count(),
            'categoriesCount' => Category::active()->count(),
            'popularCategories' => Category::popular()->limit(5)->get(),
            'recentArticles' => Article::published()
                ->latest('published_at')
                ->limit(5)
                ->get(),
        ];

        return view('newsletter.subscribe', $stats);
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getBreakingNews()
    {
        return Article::published()
            ->where('is_breaking', true)
            ->latest('published_at')
            ->limit(5)
            ->get();
    }

    private function getFeaturedArticles()
    {
        return Article::published()
            ->featured()
            ->latest('published_at')
            ->limit(8)
            ->get();
    }

    private function getLatestNews()
    {
        return Article::published()
            ->latest('published_at')
            ->limit(12)
            ->get();
    }

    private function getPopularArticles()
    {
        return Article::published()
            ->popular(7) // Popular in last 7 days
            ->limit(6)
            ->get();
    }

    private function getTrendingTopics()
    {
        return Tag::trending()
            ->limit(15)
            ->get();
    }

    private function getMainCategories()
    {
        return Category::active()
            ->root()
            ->withCount('publishedArticles')
            ->having('published_articles_count', '>', 0)
            ->orderBy('sort_order')
            ->limit(8)
            ->get();
    }

    private function getEditorsPicks()
    {
        return Article::published()
            ->where('is_editors_pick', true)
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    private function getArticlesByType(string $type, ?string $category, int $page, int $perPage)
    {
        $query = Article::published();

        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        switch ($type) {
            case 'popular':
                $query->popular(7);
                break;
            case 'trending':
                $query->trending();
                break;
            default:
                $query->latest('published_at');
                break;
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getSearchSuggestions(string $query, int $limit = 5): array
    {
        $suggestions = [];

        // Article title suggestions
        $articleSuggestions = Article::published()
            ->where('title', 'LIKE', "%{$query}%")
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->pluck('title')
            ->toArray();

        // Tag suggestions
        $tagSuggestions = Tag::where('name', 'LIKE', "%{$query}%")
            ->orderBy('popularity_score', 'desc')
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        // Category suggestions
        $categorySuggestions = Category::active()
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('articles_count', 'desc')
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        $suggestions = array_merge($articleSuggestions, $tagSuggestions, $categorySuggestions);
        
        return array_unique(array_slice($suggestions, 0, $limit));
    }
}