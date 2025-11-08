<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Article;
use App\Services\CacheService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * CategoryController - Category Management Controller
 * 
 * Features:
 * - Category browsing and navigation
 * - Hierarchical category display
 * - Category-based article filtering
 * - SEO-optimized category pages
 * - Category management for admins
 */
class CategoryController extends Controller
{
    public function __construct(
        private CacheService $cacheService,
        private AnalyticsService $analyticsService
    ) {
        $this->middleware('auth')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of categories
     */
    public function index(): View
    {
        $categories = $this->cacheService->remember('categories_index', 3600, function () {
            return Category::active()
                ->root()
                ->withCount('publishedArticles')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });

        return view('categories.index', [
            'categories' => $categories,
            'totalArticles' => Article::published()->count(),
            'featuredCategories' => Category::featured()->limit(6)->get(),
        ]);
    }

    /**
     * Display the specified category
     */
    public function show(string $slug, Request $request): View
    {
        $category = Category::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $request->validate([
            'sort' => 'nullable|string|in:latest,popular,trending,oldest',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $sort = $request->input('sort', 'latest');
        $perPage = $request->input('per_page', 15);

        // Get articles for this category
        $articlesQuery = $category->publishedArticles()->with(['author', 'tags']);

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

        // Track category view
        $this->analyticsService->trackEvent('category_view', [
            'category_id' => $category->id,
            'category_slug' => $category->slug,
            'user_id' => auth()->id(),
        ]);

        return view('categories.show', [
            'category' => $category,
            'articles' => $articles,
            'sort' => $sort,
            'subcategories' => $category->activeChildren,
            'featuredArticles' => $category->getFeaturedArticles(3),
            'breadcrumb' => $category->breadcrumb,
            'relatedCategories' => $this->getRelatedCategories($category, 5),
        ]);
    }

    /**
     * Show category tree for navigation
     */
    public function tree(): JsonResponse
    {
        $tree = $this->cacheService->remember('category_tree', 3600, function () {
            return $this->buildCategoryTree();
        });

        return response()->json([
            'success' => true,
            'tree' => $tree,
        ]);
    }

    /**
     * Get categories for autocomplete/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $categories = Category::active()
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('articles_count', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'articles_count']);

        return response()->json([
            'success' => true,
            'categories' => $categories,
            'query' => $query,
        ]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('admin.categories.create', [
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:255',
        ]);

        try {
            $category = Category::create($validated);

            // Clear category caches
            $this->clearCategoryCaches();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category created successfully!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create category: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
            'categories' => Category::active()->where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:255',
        ]);

        // Prevent circular references in parent chain
        if ($validated['parent_id']) {
            $currentParent = Category::find($validated['parent_id']);
            $visitedIds = [];
            $maxDepth = 100; // Prevent infinite loops
            $depth = 0;
            
            while ($currentParent && $depth < $maxDepth) {
                // Check if we've encountered the current category
                if ($currentParent->id === $category->id) {
                    return back()
                        ->withInput()
                        ->withErrors(['parent_id' => 'Category cannot be a descendant of itself.']);
                }
                
                // Check for infinite loops
                if (in_array($currentParent->id, $visitedIds)) {
                    break;
                }
                
                $visitedIds[] = $currentParent->id;
                $currentParent = $currentParent->parent;
                $depth++;
            }
        }

        try {
            $category->update($validated);

            // Clear category caches
            $this->clearCategoryCaches();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category updated successfully!');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update category: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        // Check if category has articles
        if ($category->publishedArticles()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete category with published articles.']);
        }

        // Check if category has children
        if ($category->children()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete category with subcategories.']);
        }

        try {
            $category->delete();

            // Clear category caches
            $this->clearCategoryCaches();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category deleted successfully!');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }

    /**
     * Update category article counts
     */
    public function updateCounts(): JsonResponse
    {
        Gate::authorize('manage', Category::class);

        try {
            $updated = 0;
            Category::chunk(50, function ($categories) use (&$updated) {
                foreach ($categories as $category) {
                    $category->updateArticlesCount();
                    $updated++;
                }
            });

            $this->clearCategoryCaches();

            return response()->json([
                'success' => true,
                'message' => "Updated article counts for {$updated} categories.",
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update category counts: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function buildCategoryTree(?int $parentId = null): array
    {
        $categories = Category::active()
            ->where('parent_id', $parentId)
            ->withCount('publishedArticles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tree = [];
        foreach ($categories as $category) {
            $node = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => route('categories.show', $category->slug),
                'articles_count' => $category->published_articles_count,
                'children' => $this->buildCategoryTree($category->id),
            ];
            $tree[] = $node;
        }

        return $tree;
    }

    private function getRelatedCategories(Category $category, int $limit): \Illuminate\Support\Collection
    {
        // Get categories that have articles with similar tags
        return Category::active()
            ->where('id', '!=', $category->id)
            ->whereHas('publishedArticles.tags', function ($query) use ($category) {
                $tagIds = $category->publishedArticles()
                    ->with('tags')
                    ->get()
                    ->pluck('tags')
                    ->flatten()
                    ->pluck('id')
                    ->unique();

                $query->whereIn('tags.id', $tagIds);
            })
            ->withCount('publishedArticles')
            ->orderBy('published_articles_count', 'desc')
            ->limit($limit)
            ->get();
    }

    private function clearCategoryCaches(): void
    {
        $this->cacheService->forget('categories_index');
        $this->cacheService->forget('category_tree');
        $this->cacheService->forget('featured_categories');
        $this->cacheService->forget('active_categories');
    }
}