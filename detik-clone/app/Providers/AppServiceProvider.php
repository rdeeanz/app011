<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Article;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Validate required analytics configuration
        $this->validateAnalyticsConfig();
        
        // Article gates
        Gate::define('create', function (?User $user) {
            return $user && in_array($user->role, ['admin', 'editor', 'author']);
        });

        Gate::define('view', function (?User $user, Article $article) {
            if ($article->isPublished()) {
                return true;
            }
            return $user && ($user->id === $article->author_id || in_array($user->role, ['admin', 'editor']));
        });

        Gate::define('update', function (?User $user, Article $article) {
            return $user && ($user->id === $article->author_id || in_array($user->role, ['admin', 'editor']));
        });

        Gate::define('delete', function (?User $user, Article $article) {
            return $user && ($user->id === $article->author_id || in_array($user->role, ['admin', 'editor']));
        });

        Gate::define('approve', function (?User $user, Article $article) {
            return $user && in_array($user->role, ['admin', 'editor']);
        });

        Gate::define('comment', function (?User $user, Article $article) {
            return $user && $article->allow_comments;
        });
    }

    /**
     * Validate required analytics configuration
     */
    private function validateAnalyticsConfig(): void
    {
        $analyticsSalt = config('analytics.salt');
        
        if (empty($analyticsSalt)) {
            throw new \RuntimeException(
                'ANALYTICS_SALT environment variable is required but not set. ' .
                'Please set a secure random string for ANALYTICS_SALT in your .env file.'
            );
        }
    }
}
