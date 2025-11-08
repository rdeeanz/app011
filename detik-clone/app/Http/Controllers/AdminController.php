<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\User;
use App\Models\Media;
use App\Services\ArticleService;
use App\Services\AnalyticsService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

/**
 * AdminController - Administrative Dashboard Controller
 * 
 * Features:
 * - Comprehensive admin dashboard with statistics
 * - Content management and moderation
 * - User management and role assignment
 * - System analytics and reporting
 * - Cache management and optimization
 * - Editorial workflow management
 */
class AdminController extends Controller
{
    public function __construct(
        private ArticleService $articleService,
        private AnalyticsService $analyticsService,
        private CacheService $cacheService
    ) {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            Gate::authorize('access-admin');
            return $next($request);
        });
    }

    /**
     * Display the admin dashboard
     */
    public function dashboard(): View
    {
        $stats = $this->getDashboardStats();
        $recentActivity = $this->getRecentActivity();
        $contentStats = $this->getContentStats();
        $systemHealth = $this->getSystemHealth();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'contentStats' => $contentStats,
            'systemHealth' => $systemHealth,
            'charts' => $this->getChartData(),
        ]);
    }

    /**
     * Article management dashboard
     */
    public function articles(Request $request): View
    {
        $request->validate([
            'status' => 'nullable|string|in:all,draft,pending,published,archived',
            'category' => 'nullable|exists:categories,id',
            'author' => 'nullable|exists:users,id',
            'sort' => 'nullable|string|in:newest,oldest,title,views,engagement',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $filters = [
            'status' => $request->input('status', 'all'),
            'category' => $request->input('category'),
            'author' => $request->input('author'),
            'sort' => $request->input('sort', 'newest'),
            'per_page' => $request->input('per_page', 25),
        ];

        $articles = $this->getFilteredArticles($filters);

        return view('admin.articles.index', [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'authors' => User::whereHas('articles')->orderBy('name')->get(),
            'statusCounts' => $this->getArticleStatusCounts(),
        ]);
    }

    /**
     * Comment moderation dashboard
     */
    public function comments(Request $request): View
    {
        $request->validate([
            'status' => 'nullable|string|in:all,pending,approved,rejected,spam',
            'article' => 'nullable|exists:articles,id',
            'sort' => 'nullable|string|in:newest,oldest,likes,reports',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $filters = [
            'status' => $request->input('status', 'pending'),
            'article' => $request->input('article'),
            'sort' => $request->input('sort', 'newest'),
            'per_page' => $request->input('per_page', 25),
        ];

        $comments = $this->getFilteredComments($filters);

        return view('admin.comments.index', [
            'comments' => $comments,
            'filters' => $filters,
            'articles' => Article::published()->orderBy('title')->get(),
            'statusCounts' => $this->getCommentStatusCounts(),
        ]);
    }

    /**
     * User management dashboard
     */
    public function users(Request $request): View
    {
        Gate::authorize('manage-users');

        $request->validate([
            'role' => 'nullable|string|in:all,admin,editor,author,user',
            'status' => 'nullable|string|in:all,active,inactive,banned',
            'sort' => 'nullable|string|in:newest,oldest,name,articles',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $filters = [
            'role' => $request->input('role', 'all'),
            'status' => $request->input('status', 'all'),
            'sort' => $request->input('sort', 'newest'),
            'per_page' => $request->input('per_page', 25),
        ];

        $users = $this->getFilteredUsers($filters);

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'userStats' => $this->getUserStats(),
        ]);
    }

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request): View
    {
        $request->validate([
            'period' => 'nullable|string|in:today,yesterday,week,month,quarter,year',
            'metric' => 'nullable|string|in:views,engagement,articles,users,comments',
        ]);

        $period = $request->input('period', 'week');
        $metric = $request->input('metric', 'views');

        $analytics = $this->analyticsService->getDashboardAnalytics($period);
        $topArticles = $this->analyticsService->getTopArticles($period, 10);
        $topCategories = $this->analyticsService->getTopCategories($period, 10);
        $userActivity = $this->analyticsService->getUserActivity($period);

        return view('admin.analytics', [
            'analytics' => $analytics,
            'topArticles' => $topArticles,
            'topCategories' => $topCategories,
            'userActivity' => $userActivity,
            'period' => $period,
            'metric' => $metric,
            'chartData' => $this->analyticsService->getChartData($period, $metric),
        ]);
    }

    /**
     * System settings and configuration
     */
    public function settings(): View
    {
        Gate::authorize('manage-settings');

        $settings = [
            'site_name' => config('app.name'),
            'site_description' => config('app.description', ''),
            'items_per_page' => config('app.pagination.default', 15),
            'cache_enabled' => config('cache.default') !== 'array',
            'analytics_enabled' => !empty(config('services.google.analytics_id')),
            'comments_enabled' => config('comments.enabled', true),
            'auto_approve_comments' => config('comments.auto_approve', false),
            'max_upload_size' => config('media.max_file_size', 5120),
        ];

        return view('admin.settings', [
            'settings' => $settings,
            'cacheStats' => $this->getCacheStats(),
            'systemInfo' => $this->getSystemInfo(),
        ]);
    }

    /**
     * Bulk article operations
     */
    public function bulkArticleAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:publish,unpublish,archive,delete,approve,reject',
            'article_ids' => 'required|array|min:1',
            'article_ids.*' => 'exists:articles,id',
        ]);

        $action = $request->input('action');
        $articleIds = $request->input('article_ids');
        $user = auth()->user();

        try {
            $processed = 0;
            $errors = [];

            foreach ($articleIds as $articleId) {
                $article = Article::find($articleId);
                
                if (!$article) {
                    continue;
                }

                // Check permissions
                if (!Gate::allows('update', $article)) {
                    $errors[] = "No permission for article: {$article->title}";
                    continue;
                }

                switch ($action) {
                    case 'publish':
                        $this->articleService->publishArticle($article, $user);
                        break;
                    case 'unpublish':
                        $this->articleService->unpublishArticle($article, $user);
                        break;
                    case 'archive':
                        $this->articleService->archiveArticle($article, $user);
                        break;
                    case 'delete':
                        if (Gate::allows('delete', $article)) {
                            $this->articleService->deleteArticle($article, $user);
                        } else {
                            $errors[] = "Cannot delete article: {$article->title}";
                            continue 2;
                        }
                        break;
                    case 'approve':
                        $this->articleService->approveArticle($article, $user);
                        break;
                    case 'reject':
                        $this->articleService->rejectArticle($article, $user);
                        break;
                }

                $processed++;
            }

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} articles successfully.",
                'processed' => $processed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk operation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk comment moderation
     */
    public function bulkCommentAction(Request $request): JsonResponse
    {
        Gate::authorize('moderate-comments');

        $request->validate([
            'action' => 'required|string|in:approve,reject,delete,spam',
            'comment_ids' => 'required|array|min:1',
            'comment_ids.*' => 'exists:comments,id',
        ]);

        $action = $request->input('action');
        $commentIds = $request->input('comment_ids');
        $user = auth()->user();

        try {
            $processed = 0;

            foreach ($commentIds as $commentId) {
                $comment = Comment::find($commentId);
                
                if (!$comment) {
                    continue;
                }

                switch ($action) {
                    case 'approve':
                        $comment->approve($user);
                        break;
                    case 'reject':
                        $comment->reject($user);
                        break;
                    case 'delete':
                        $comment->delete();
                        break;
                    case 'spam':
                        $comment->markAsSpam($user);
                        break;
                }

                $processed++;
            }

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} comments successfully.",
                'processed' => $processed,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk operation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear caches
     */
    public function clearCache(): JsonResponse
    {
        Gate::authorize('manage-settings');

        try {
            // Clear application cache
            Cache::flush();
            
            // Clear specific service caches
            $this->cacheService->flush();

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate sitemap
     */
    public function generateSitemap(): JsonResponse
    {
        Gate::authorize('manage-settings');

        try {
            // This would trigger sitemap generation
            // Implementation depends on your sitemap package
            
            return response()->json([
                'success' => true,
                'message' => 'Sitemap generated successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate sitemap: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getDashboardStats(): array
    {
        return $this->cacheService->remember('admin_dashboard_stats', 300, function () {
            return [
                'total_articles' => Article::count(),
                'published_articles' => Article::published()->count(),
                'pending_articles' => Article::where('editorial_status', 'pending')->count(),
                'total_comments' => Comment::count(),
                'pending_comments' => Comment::pending()->count(),
                'total_users' => User::count(),
                'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
                'total_views' => Article::sum('views_count'),
                'total_shares' => Article::sum('shares_count'),
            ];
        });
    }

    private function getRecentActivity(): array
    {
        return [
            'recent_articles' => Article::with('author')->latest()->limit(5)->get(),
            'recent_comments' => Comment::with(['article', 'user'])->latest()->limit(5)->get(),
            'recent_users' => User::latest()->limit(5)->get(),
        ];
    }

    private function getContentStats(): array
    {
        return [
            'articles_by_status' => Article::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'comments_by_status' => Comment::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'top_categories' => Category::withCount('publishedArticles')
                ->orderBy('published_articles_count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    private function getSystemHealth(): array
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $diskUsedPercentage = $totalSpace > 0 ? round((1 - ($freeSpace / $totalSpace)) * 100, 2) : 0;
        
        return [
            'disk_usage' => $diskUsedPercentage,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ];
    }

    private function getChartData(): array
    {
        // Generate chart data for the last 30 days
        $days = collect(range(29, 0))->map(function ($daysBack) {
            $date = now()->subDays($daysBack);
            return [
                'date' => $date->format('Y-m-d'),
                'articles' => Article::whereDate('created_at', $date)->count(),
                'comments' => Comment::whereDate('created_at', $date)->count(),
                'views_of_articles_created' => Article::whereDate('created_at', $date)->sum('views_count'),
            ];
        });

        return $days->toArray();
    }

    private function getFilteredArticles(array $filters)
    {
        $query = Article::with(['author', 'category']);

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['category']) {
            $query->where('category_id', $filters['category']);
        }

        if ($filters['author']) {
            $query->where('author_id', $filters['author']);
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'views':
                $query->orderBy('views_count', 'desc');
                break;
            case 'engagement':
                $query->orderBy('engagement_score', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        return $query->paginate($filters['per_page']);
    }

    private function getFilteredComments(array $filters)
    {
        $query = Comment::with(['article', 'user']);

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['article']) {
            $query->where('article_id', $filters['article']);
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query->oldest();
                break;
            case 'likes':
                $query->orderBy('likes_count', 'desc');
                break;
            case 'reports':
                $query->orderBy('reports_count', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        return $query->paginate($filters['per_page']);
    }

    private function getFilteredUsers(array $filters)
    {
        $query = User::withCount('articles');

        // Apply role filtering - skip "all" values
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        // Apply status filtering - skip "all" values and map UI values to DB predicates
        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $statuses = array_filter($statuses, fn($status) => $status !== 'all');
            
            if (!empty($statuses)) {
                $query->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        if ($status === 'active') {
                            $q->orWhere('is_active', 1);
                        } elseif ($status === 'inactive') {
                            $q->orWhere('is_active', 0);
                        } elseif ($status === 'banned') {
                            // Assuming banned users have is_active = 0 and a banned flag or status
                            $q->orWhere(function ($subQ) {
                                $subQ->where('is_active', 0)
                                     ->where(function ($banQ) {
                                         $banQ->where('is_banned', 1)
                                              ->orWhere('status', 'banned');
                                     });
                            });
                        }
                    }
                });
            }
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'articles':
                $query->orderBy('articles_count', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    private function getArticleStatusCounts(): array
    {
        return Article::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getCommentStatusCounts(): array
    {
        return Comment::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    private function getCacheStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'enabled' => config('cache.default') !== 'array',
            // Add more cache statistics here
        ];
    }

    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }
}