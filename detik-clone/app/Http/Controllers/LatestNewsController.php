<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LatestNewsController - Dedicated controller for latest news with pagination
 * 
 * Features:
 * - Optimized pagination with 10 items per page
 * - AJAX loading for seamless user experience
 * - SEO-friendly URLs
 */
class LatestNewsController extends Controller
{
    /**
     * Display paginated latest news
     */
    public function index(Request $request): Response|JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:20',
        ]);

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Get paginated latest news with optimal relations
        $latestNews = Article::published()
            ->with(['category:id,name,slug,color', 'author:id,name,avatar'])
            ->select([
                'id', 'title', 'slug', 'excerpt', 'featured_image', 
                'published_at', 'views_count', 'category_id', 'author_id'
            ])
            ->latest('published_at')
            ->paginate($perPage, ['*'], 'page', $page);

        // Add SEO metadata
        $metaData = [
            'title' => 'Berita Terbaru - ' . config('app.name'),
            'description' => 'Dapatkan informasi berita terbaru dan terkini dari berbagai kategori. Update setiap hari dengan berita akurat dan terpercaya.',
            'canonical' => route('latest-news.index'),
        ];

        // For AJAX requests, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $latestNews->items(),
                'pagination' => [
                    'current_page' => $latestNews->currentPage(),
                    'per_page' => $latestNews->perPage(),
                    'total' => $latestNews->total(),
                    'last_page' => $latestNews->lastPage(),
                    'has_more_pages' => $latestNews->hasMorePages(),
                    'next_page_url' => $latestNews->nextPageUrl(),
                    'prev_page_url' => $latestNews->previousPageUrl(),
                ],
                'meta' => $metaData,
            ]);
        }

        // For full page requests, return Inertia response
        return Inertia::render('LatestNews/Index', [
            'latestNews' => $latestNews,
            'meta' => $metaData,
        ]);
    }

    /**
     * Get latest news for homepage with pagination (6 items per page)
     */
    public function homepage(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:3|max:12',
        ]);

        $perPage = $request->input('per_page', 6);
        $page = $request->input('page', 1);

        $latestNews = Article::published()
            ->with(['category:id,name,slug,color', 'author:id,name,avatar'])
            ->select([
                'id', 'title', 'slug', 'excerpt', 'featured_image', 
                'published_at', 'views_count', 'category_id', 'author_id'
            ])
            ->latest('published_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $latestNews->items(),
            'pagination' => [
                'current_page' => $latestNews->currentPage(),
                'per_page' => $latestNews->perPage(),
                'total' => $latestNews->total(),
                'last_page' => $latestNews->lastPage(),
                'has_more_pages' => $latestNews->hasMorePages(),
            ],
            'total' => $latestNews->total(),
        ]);
    }

    /**
     * Load more news (infinite scroll support)
     */
    public function loadMore(Request $request): JsonResponse
    {
        $request->validate([
            'offset' => 'required|integer|min:0',
            'limit' => 'nullable|integer|min:5|max:20',
        ]);

        $offset = $request->input('offset');
        $limit = $request->input('limit', 10);

        $latestNews = Article::published()
            ->with(['category:id,name,slug,color', 'author:id,name,avatar'])
            ->select([
                'id', 'title', 'slug', 'excerpt', 'featured_image', 
                'published_at', 'views_count', 'category_id', 'author_id'
            ])
            ->latest('published_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $hasMore = Article::published()
            ->offset($offset + $limit)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => $latestNews,
            'has_more' => $hasMore,
            'next_offset' => $offset + $limit,
        ]);
    }
}