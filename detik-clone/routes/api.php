<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes for Articles
Route::prefix('articles')->group(function () {
    // Public routes
    Route::get('/', [ArticleController::class, 'index'])->name('api.articles.index');
    Route::get('/search', [ArticleController::class, 'search'])->name('api.articles.search');
    Route::get('/{article}', [ArticleController::class, 'show'])->name('api.articles.show');
    
    // Public sharing
    Route::post('/{article}/share', [ArticleController::class, 'share'])->name('api.articles.share');
    
    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{article}/bookmark', [ArticleController::class, 'bookmark'])->name('api.articles.bookmark');
        Route::delete('/{article}/bookmark', [ArticleController::class, 'unbookmark'])->name('api.articles.unbookmark');
    });
});