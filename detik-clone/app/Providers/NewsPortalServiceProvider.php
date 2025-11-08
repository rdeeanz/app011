<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ArticleService;
use App\Services\MediaService;
use App\Services\AnalyticsService;
use App\Services\NotificationService;
use App\Services\CacheService;
use App\Repositories\ArticleRepository;

class NewsPortalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories
        $this->app->singleton(ArticleRepository::class, function ($app) {
            return new ArticleRepository($app->make(\App\Models\Article::class));
        });

        // Register services
        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService();
        });

        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });

        $this->app->singleton(ArticleService::class, function ($app) {
            return new ArticleService(
                $app->make(ArticleRepository::class),
                $app->make(MediaService::class),
                $app->make(AnalyticsService::class),
                $app->make(NotificationService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register any event listeners or other bootstrapping logic here
        
        // Example: Warm up essential caches on application boot
        if (!$this->app->runningInConsole()) {
            $this->app->make(CacheService::class)->warmUpEssentialCaches();
        }
    }
}
