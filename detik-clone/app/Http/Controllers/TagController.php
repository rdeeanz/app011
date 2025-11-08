<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Article;
use App\Services\CacheService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * TagController - Tag Management Controller
 * 
 * Features:
 * - Tag browsing and navigation
 * - Tag-based article filtering
 * - Trending tags display
 * - Tag cloud and popularity visualization
 * - Tag management for admins
 */
class TagController extends Controller
{
    public function __construct(
        private CacheService $cacheService,
        private AnalyticsService $analyticsService
    ) {
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of tags
     */
    public function index(Request $request): View
    {
        $request->validate([
            'sort' => 'nullable|string|in:name,popular,trending,recent',
            'filter' => 'nullable|string|in:all,trending,featured',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $sort = $request->input('sort', 'popular');
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 30);

        $query = Tag::query();

        // Apply filters
        switch ($filter) {
            case 'trending':
                $query->trending();
                break;
            case 'featured':
                $query->featured();
                break;
            default:
                $query->used(); // Only show tags with articles
                break;
        }

        // Apply sorting
        switch ($sort) {
            case 'name':
                $query->alphabetical();
                break;
            case 'trending':
                $query->orderBy('popularity_score', 'desc');
                break;
            case 'recent':
                $query->recent(30);
                break;
            default:
                $query->popular();
                break;
        }

        $tags = $query->paginate($perPage);

        return view('tags.index', [
            'tags' => $tags,
            'sort' => $sort,
            'filter' => $filter,
            'trendingTags' => Tag::getTrendingTags(10),
            'popularTags' => Tag::getPopularTags(15),
            'tagCloud' => $this->generateTagCloud(),
        ]);
    }

    /**
     * Display the specified tag
     */
    public function show(string $slug, Request $request): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $request->validate([
            'sort' => 'nullable|string|in:latest,popular,trending,oldest',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $sort = $request->input('sort', 'latest');
        $perPage = $request->input('per_page', 15);

        // Get articles for this tag
        $articlesQuery = $tag->publishedArticles()->with(['author', 'category']);

        switch ($sort) {
            case 'popular':
                $articlesQuery->popular(7);
                break;
            case 'trending':
                $articlesQuery->trending();
                break;
            case 'oldest':
                $articlesQuery->oldest('published_at');
                break;
            default:
                $articlesQuery->latest('published_at');
                break;
        }

        $articles = $articlesQuery->paginate($perPage);

        // Track tag view
        $this->analyticsService->trackEvent('tag_view', [
            'tag_id' => $tag->id,
            'tag_slug' => $tag->slug,
            'user_id' => auth()->id(),
        ]);

        return view('tags.show', [
            'tag' => $tag,
            'articles' => $articles,
            'sort' => $sort,
            'relatedTags' => $tag->getRelatedTags(10),
            'popularArticles' => $tag->getPopularArticles(5),
            'tagStats' => [
                'total_articles' => $tag->usage_count,
                'recent_articles' => $tag->publishedArticles()
                    ->where('published_at', '>=', now()->subDays(30))
                    ->count(),
                'trending_score' => $tag->trending_score,
            ],
        ]);
    }

    /**
     * Get trending tags
     */
    public function trending(): JsonResponse
    {
        $trending = $this->cacheService->remember('trending_tags_api', 900, function () {
            return Tag::getTrendingTags(20);
        });

        return response()->json([
            'success' => true,
            'tags' => $trending,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get popular tags
     */
    public function popular(): JsonResponse
    {
        $popular = $this->cacheService->remember('popular_tags_api', 1800, function () {
            return Tag::getPopularTags(30);
        });

        return response()->json([
            'success' => true,
            'tags' => $popular,
            'updated_at' => now(),
        ]);
    }

    /**
     * Search tags for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $tags = Tag::where('name', 'LIKE', "%{$query}%")
            ->used()
            ->orderBy('popularity_score', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'articles_count', 'popularity_score']);

        return response()->json([
            'success' => true,
            'tags' => $tags,
            'query' => $query,
        ]);
    }

    /**
     * Get tag suggestions for article creation
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:10000',
            'title' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $content = $request->input('content', '');
        $title = $request->input('title', '');
        $categoryId = $request->input('category_id');
        $limit = $request->input('limit', 10);

        $suggestions = $this->generateTagSuggestions($title . ' ' . $content, $categoryId, $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Show the form for creating a new tag
     */
    public function create(): View
    {
        Gate::authorize('create', Tag::class);

        return view('admin.tags.create');
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Tag::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ]);

        try {
            $tag = Tag::create($validated);

            // Clear tag caches
            $this->clearTagCaches();

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag created successfully!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create tag: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified tag
     */
    public function edit(Tag $tag): View
    {
        Gate::authorize('update', $tag);

        return view('admin.tags.edit', [
            'tag' => $tag,
            'relatedTags' => $tag->getRelatedTags(10),
            'tagStats' => [
                'articles_count' => $tag->usage_count,
                'trending_score' => $tag->trending_score,
                'popularity_score' => $tag->popularity_score,
            ],
        ]);
    }

    /**
     * Update the specified tag
     */
    public function update(Request $request, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $tag);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $tag->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ]);

        try {
            $tag->update($validated);

            // Update popularity score if needed
            $tag->updatePopularityScore();

            // Clear tag caches
            $this->clearTagCaches();

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag updated successfully!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update tag: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified tag
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        // Check if tag has articles
        if ($tag->publishedArticles()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete tag with published articles.']);
        }

        try {
            $tag->delete();

            // Clear tag caches
            $this->clearTagCaches();

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Tag deleted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete tag: ' . $e->getMessage()]);
        }
    }

    /**
     * Update trending tags
     */
    public function updateTrending(): JsonResponse
    {
        Gate::authorize('manage', Tag::class);

        try {
            Tag::updateTrendingTags();

            $this->clearTagCaches();

            return response()->json([
                'success' => true,
                'message' => 'Trending tags updated successfully!',
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update trending tags: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function generateTagCloud(): array
    {
        return $this->cacheService->remember('tag_cloud', 3600, function () {
            return Tag::used()
                ->orderBy('popularity_score', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($tag) {
                    return [
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'url' => route('tags.show', $tag->slug),
                        'count' => $tag->usage_count,
                        'weight' => $this->calculateTagWeight($tag->popularity_score),
                        'color' => $tag->color,
                    ];
                })
                ->toArray();
        });
    }

    private function calculateTagWeight(float $popularityScore): int
    {
        // Convert popularity score to weight (1-5)
        if ($popularityScore >= 10) return 5;
        if ($popularityScore >= 7) return 4;
        if ($popularityScore >= 4) return 3;
        if ($popularityScore >= 2) return 2;
        return 1;
    }

    private function generateTagSuggestions(string $text, ?int $categoryId, int $limit): array
    {
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, function ($word) {
            return strlen($word) > 3; // Only words longer than 3 characters
        });

        // Get existing tags that match words in the content
        $matchingTags = Tag::whereIn('name', $words)
            ->orWhere(function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere('name', 'LIKE', "%{$word}%");
                }
            })
            ->orderBy('popularity_score', 'desc')
            ->limit($limit)
            ->get();

        // If we have a category, also suggest popular tags from that category
        if ($categoryId && $matchingTags->count() < $limit) {
            $categoryTags = Tag::whereHas('articles.category', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
                ->orderBy('popularity_score', 'desc')
                ->limit($limit - $matchingTags->count())
                ->get();

            $matchingTags = $matchingTags->merge($categoryTags)->unique('id');
        }

        return $matchingTags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'usage_count' => $tag->usage_count,
                'popularity_score' => $tag->popularity_score,
            ];
        })->toArray();
    }

    private function clearTagCaches(): void
    {
        $this->cacheService->forget('trending_tags');
        $this->cacheService->forget('popular_tags');
        $this->cacheService->forget('featured_tags');
        $this->cacheService->forget('tag_cloud');
        $this->cacheService->forget('trending_tags_api');
        $this->cacheService->forget('popular_tags_api');
    }
}