<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Article;
use App\Models\Category;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\LatestNewsController;

/*
|--------------------------------------------------------------------------
| News Portal Routes
|--------------------------------------------------------------------------
|
| Routes for the Detik.com clone frontend using Inertia.js + Vue 3
|
*/

// Homepage - Main news feed
Route::get('/', function () {
    $featuredArticles = Article::published()
        ->featured()
        ->with(['author', 'category'])
        ->limit(5)
        ->get();
        
    $breakingNews = Article::published()
        ->breaking()
        ->with(['author', 'category'])
        ->limit(3)
        ->get();
        
    $latestNews = Article::published()
        ->with(['author', 'category'])
        ->latest('published_at')
        ->limit(10)
        ->get();
        
    $categories = Category::active()
        ->root()
        ->with('activeChildren')
        ->get();

    return Inertia::render('Home', [
        'featuredArticles' => $featuredArticles,
        'breakingNews' => $breakingNews,
        'latestNews' => $latestNews,
        'categories' => $categories,
    ]);
})->name('home');

// Latest News routes with pagination
Route::prefix('berita-terbaru')->name('latest-news.')->group(function () {
    Route::get('/', [LatestNewsController::class, 'index'])->name('index');
    Route::get('/homepage', [LatestNewsController::class, 'homepage'])->name('homepage');
    Route::post('/load-more', [LatestNewsController::class, 'loadMore'])->name('load-more');
});

// Article Resource Routes for CRUD operations (MOVED TO TOP)
Route::resource('articles', ArticleController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);

// Protected article routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::patch('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    
    // Additional protected article routes
    Route::post('/articles/{article}/bookmark', [ArticleController::class, 'bookmark'])->name('articles.bookmark');
    Route::delete('/articles/{article}/bookmark', [ArticleController::class, 'unbookmark'])->name('articles.unbookmark');
    Route::post('/articles/{article}/comment', [ArticleController::class, 'comment'])->name('articles.comment');
});

// Public article routes (anyone can access)
Route::post('/articles/{article}/share', [ArticleController::class, 'share'])->name('articles.share');

// Authentication Routes (basic)
Route::get('/login', function() { return view('auth.login'); })->name('login');
Route::post('/login', function() { return redirect('/'); })->name('login.submit');
Route::post('/logout', function() { Auth::logout(); return redirect('/'); })->name('logout');
Route::get('/register', function() { return view('auth.register'); })->name('register');
Route::post('/register', function() { return redirect('/'); })->name('register.submit');



// Category pages
Route::get('/kategori/{category:slug}', function (Category $category) {
    $articles = Article::published()
        ->byCategory($category->id)
        ->with(['author', 'category'])
        ->latest('published_at')
        ->paginate(20);

    $allCategories = Category::active()
        ->root()
        ->get();
        
    $popularArticles = Article::published()
        ->with(['author', 'category'])
        ->orderBy('views', 'desc')
        ->limit(5)
        ->get();

    return Inertia::render('Category', [
        'category' => $category,
        'articles' => $articles,
        'allCategories' => $allCategories,
        'popularArticles' => $popularArticles,
    ]);
})->name('category.show');


// Search - Optimized search with pagination
Route::get('/cari', function () {
    $query = request('q');
    $categories = Category::active()->root()->with('activeChildren')->get();
    
    if ($query && strlen(trim($query)) >= 2) {
        // Escape SQL LIKE wildcard characters to treat them as literals
        $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        
        // Search in title, excerpt, and content with escaped query
        $articles = Article::published()
            ->where(function ($q) use ($escapedQuery) {
                $q->where('title', 'like', "%{$escapedQuery}%")
                  ->orWhere('excerpt', 'like', "%{$escapedQuery}%")
                  ->orWhere('content', 'like', "%{$escapedQuery}%");
            })
            ->with(['author:id,name,avatar', 'category:id,name,slug,color'])
            ->select([
                'id', 'title', 'slug', 'excerpt', 'featured_image',
                'published_at', 'views_count', 'category_id', 'author_id'
            ])
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString(); // Preserve search query in pagination links
    } else {
        // Return a complete pagination structure for consistency
        $articles = new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            15,
            1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    return Inertia::render('Search', [
        'query' => $query,
        'articles' => $articles,
        'categories' => $categories,
    ]);
})->name('search');

// Tag pages
Route::get('/tag/{tag:slug}', function (\App\Models\Tag $tag) {
    $articles = $tag->publishedArticles()
        ->with(['author', 'category'])
        ->latest('published_at')
        ->paginate(20);

    return Inertia::render('Tag', [
        'tag' => $tag,
        'articles' => $articles,
    ]);
})->name('tag.show');



// Category routes
Route::get('/categories/{category}', function(Category $category) {
    return redirect()->route('category.show', $category->slug);
})->name('categories.show');

// Tag routes
Route::get('/tags/{tag}', function(\App\Models\Tag $tag) {
    return redirect()->route('tag.show', $tag->slug);
})->name('tags.show');

// Fallback route for SPA (must be last)
Route::fallback(function () {
    return Inertia::render('Error', [
        'status' => 404
    ]);
});