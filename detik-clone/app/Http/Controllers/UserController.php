<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use App\Services\AnalyticsService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

/**
 * UserController - User Profile and Account Management
 * 
 * Features:
 * - User profile management and customization
 * - Account settings and preferences
 * - Password and security management
 * - User activity tracking and statistics
 * - Social features (following, bookmarks)
 * - Notification preferences
 * - Avatar and media management
 */
class UserController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private CacheService $cacheService
    ) {
        $this->middleware('auth')->except(['show', 'articles']);
        $this->middleware('verified')->except(['show', 'articles']);
    }

    /**
     * Display user profile page
     */
    public function show(User $user): View
    {
        $user->load(['articles' => function($query) {
            $query->published()->latest()->limit(6);
        }]);

        $stats = $this->getUserPublicStats($user);
        $recentActivity = $this->getUserRecentActivity($user);
        
        // Track profile view
        $this->analyticsService->trackEvent('profile_view', [
            'user_id' => $user->id,
            'viewer_id' => auth()->id(),
        ]);

        return view('users.show', [
            'user' => $user,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'isOwnProfile' => auth()->id() === $user->id,
            'isFollowing' => auth()->check() ? auth()->user()->isFollowing($user) : false,
        ]);
    }

    /**
     * Display user's articles
     */
    public function articles(Request $request, User $user): View
    {
        $request->validate([
            'category' => 'nullable|exists:categories,id',
            'sort' => 'nullable|string|in:newest,oldest,popular,trending',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $filters = [
            'category' => $request->input('category'),
            'sort' => $request->input('sort', 'newest'),
            'per_page' => $request->input('per_page', 12),
        ];

        $articles = $this->getUserArticles($user, $filters);

        return view('users.articles', [
            'user' => $user,
            'articles' => $articles,
            'filters' => $filters,
            'stats' => $this->getUserArticleStats($user),
        ]);
    }

    /**
     * Show user profile edit form
     */
    public function edit(): View
    {
        $user = auth()->user();
        
        return view('users.edit', [
            'user' => $user,
            'preferences' => $user->preferences ?? [],
            'socialLinks' => $user->social_links ?? [],
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:50',
            'linkedin' => 'nullable|string|max:100',
            'github' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $updateData = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'bio' => $request->input('bio'),
                'location' => $request->input('location'),
                'website' => $request->input('website'),
            ];

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $path = $avatar->store('avatars', 'public');
                
                // Delete old avatar
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                $updateData['avatar'] = $path;
            }

            // Handle social links
            $socialLinks = [];
            if ($request->filled('twitter')) {
                $socialLinks['twitter'] = $request->input('twitter');
            }
            if ($request->filled('linkedin')) {
                $socialLinks['linkedin'] = $request->input('linkedin');
            }
            if ($request->filled('github')) {
                $socialLinks['github'] = $request->input('github');
            }
            
            $updateData['social_links'] = $socialLinks;

            $user->update($updateData);

            // Clear user cache
            $this->cacheService->forget("user_profile_{$user->id}");

            // Track profile update
            $this->analyticsService->trackEvent('profile_updated', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('users.edit')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Show password change form
     */
    public function showPasswordForm(): View
    {
        return view('users.password');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = auth()->user();
            
            $user->update([
                'password' => Hash::make($request->input('password')),
            ]);

            // Track password change
            $this->analyticsService->trackEvent('password_changed', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('users.password')
                ->with('success', 'Password updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Show notification preferences
     */
    public function showNotifications(): View
    {
        $user = auth()->user();
        $preferences = $user->notification_preferences ?? $this->getDefaultNotificationPreferences();

        return view('users.notifications', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'email_comments' => 'boolean',
            'email_likes' => 'boolean',
            'email_follows' => 'boolean',
            'email_articles' => 'boolean',
            'push_comments' => 'boolean',
            'push_likes' => 'boolean',
            'push_follows' => 'boolean',
            'push_articles' => 'boolean',
            'weekly_digest' => 'boolean',
            'marketing_emails' => 'boolean',
        ]);

        try {
            $user = auth()->user();
            
            $preferences = [
                'email' => [
                    'comments' => $request->boolean('email_comments'),
                    'likes' => $request->boolean('email_likes'),
                    'follows' => $request->boolean('email_follows'),
                    'articles' => $request->boolean('email_articles'),
                ],
                'push' => [
                    'comments' => $request->boolean('push_comments'),
                    'likes' => $request->boolean('push_likes'),
                    'follows' => $request->boolean('push_follows'),
                    'articles' => $request->boolean('push_articles'),
                ],
                'digest' => [
                    'weekly' => $request->boolean('weekly_digest'),
                ],
                'marketing' => [
                    'emails' => $request->boolean('marketing_emails'),
                ],
            ];

            $user->update([
                'notification_preferences' => $preferences,
            ]);

            // Track preference update
            $this->analyticsService->trackEvent('notification_preferences_updated', [
                'user_id' => $user->id,
                'preferences' => $preferences,
            ]);

            return redirect()->route('users.notifications')
                ->with('success', 'Notification preferences updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update preferences: ' . $e->getMessage());
        }
    }

    /**
     * Show user dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        $stats = $this->getUserDashboardStats($user);
        $recentArticles = $this->getUserRecentArticles($user);
        $recentComments = $this->getUserRecentComments($user);
        $bookmarks = $this->getUserBookmarks($user);

        return view('users.dashboard', [
            'stats' => $stats,
            'recentArticles' => $recentArticles,
            'recentComments' => $recentComments,
            'bookmarks' => $bookmarks,
            'analyticsData' => $this->getUserAnalytics($user),
        ]);
    }

    /**
     * Show user's bookmarks
     */
    public function bookmarks(Request $request): View
    {
        $user = auth()->user();

        $request->validate([
            'category' => 'nullable|exists:categories,id',
            'sort' => 'nullable|string|in:newest,oldest,popular',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);

        $filters = [
            'category' => $request->input('category'),
            'sort' => $request->input('sort', 'newest'),
            'per_page' => $request->input('per_page', 12),
        ];

        $bookmarks = $this->getUserBookmarkedArticles($user, $filters);

        return view('users.bookmarks', [
            'bookmarks' => $bookmarks,
            'filters' => $filters,
        ]);
    }

    /**
     * Follow/unfollow user
     */
    public function toggleFollow(User $user): JsonResponse
    {
        $currentUser = auth()->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'error' => 'You cannot follow yourself.',
            ], 400);
        }

        try {
            $isFollowing = $currentUser->isFollowing($user);

            if ($isFollowing) {
                $currentUser->unfollow($user);
                $action = 'unfollowed';
            } else {
                $currentUser->follow($user);
                $action = 'followed';
            }

            // Clear follower caches
            $this->cacheService->forget("user_followers_{$user->id}");
            $this->cacheService->forget("user_following_{$currentUser->id}");

            // Track follow action
            $this->analyticsService->trackEvent('user_' . $action, [
                'follower_id' => $currentUser->id,
                'followed_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'action' => $action,
                'is_following' => !$isFollowing,
                'followers_count' => $user->fresh()->followers_count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to ' . ($isFollowing ? 'unfollow' : 'follow') . ' user.',
            ], 500);
        }
    }

    /**
     * Delete user account
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
            'confirmation' => 'required|in:DELETE',
        ]);

        try {
            $user = auth()->user();

            // Log the account deletion
            $this->analyticsService->trackEvent('account_deleted', [
                'user_id' => $user->id,
                'deletion_date' => now(),
            ]);

            // Delete avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Clear user caches
            $this->cacheService->forget("user_profile_{$user->id}");
            $this->cacheService->forget("user_stats_{$user->id}");

            // Logout and delete account
            Auth::logout();
            $user->delete();

            return redirect()->route('home')
                ->with('success', 'Your account has been deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getUserPublicStats(User $user): array
    {
        return $this->cacheService->remember("user_public_stats_{$user->id}", 3600, function () use ($user) {
            return [
                'articles_count' => $user->articles()->published()->count(),
                'comments_count' => $user->comments()->approved()->count(),
                'followers_count' => $user->followers_count ?? 0,
                'following_count' => $user->following_count ?? 0,
                'total_views' => $user->articles()->published()->sum('views_count'),
                'total_likes' => $user->articles()->published()->sum('likes_count'),
                'member_since' => $user->created_at,
            ];
        });
    }

    private function getUserRecentActivity(User $user): array
    {
        return [
            'recent_articles' => $user->articles()
                ->published()
                ->with('category')
                ->latest()
                ->limit(3)
                ->get(),
            'recent_comments' => $user->comments()
                ->approved()
                ->with('article')
                ->latest()
                ->limit(3)
                ->get(),
        ];
    }

    private function getUserArticles(User $user, array $filters)
    {
        $query = $user->articles()->published()->with(['category', 'tags']);

        if ($filters['category']) {
            $query->where('category_id', $filters['category']);
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'trending':
                $query->orderBy('engagement_score', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        return $query->paginate($filters['per_page']);
    }

    private function getUserArticleStats(User $user): array
    {
        return [
            'total' => $user->articles()->published()->count(),
            'this_month' => $user->articles()->published()
                ->where('published_at', '>=', now()->startOfMonth())
                ->count(),
            'total_views' => $user->articles()->published()->sum('views_count'),
            'avg_views' => $user->articles()->published()->avg('views_count'),
        ];
    }

    private function getUserDashboardStats(User $user): array
    {
        return $this->cacheService->remember("user_dashboard_stats_{$user->id}", 1800, function () use ($user) {
            return [
                'total_articles' => $user->articles()->count(),
                'published_articles' => $user->articles()->published()->count(),
                'draft_articles' => $user->articles()->draft()->count(),
                'pending_articles' => $user->articles()->where('editorial_status', 'pending')->count(),
                'total_comments' => $user->comments()->count(),
                'total_views' => $user->articles()->sum('views_count'),
                'total_likes' => $user->articles()->sum('likes_count'),
                'bookmarks_count' => $user->bookmarkedArticles()->count(),
                'followers_count' => $user->followers_count ?? 0,
                'following_count' => $user->following_count ?? 0,
            ];
        });
    }

    private function getUserRecentArticles(User $user)
    {
        return $user->articles()
            ->with('category')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getUserRecentComments(User $user)
    {
        return $user->comments()
            ->with('article')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function getUserBookmarks(User $user)
    {
        return $user->bookmarkedArticles()
            ->with(['category', 'author'])
            ->latest('user_bookmarks.created_at')
            ->limit(5)
            ->get();
    }

    private function getUserBookmarkedArticles(User $user, array $filters)
    {
        $query = $user->bookmarkedArticles()->with(['category', 'author']);

        if ($filters['category']) {
            $query->where('category_id', $filters['category']);
        }

        switch ($filters['sort']) {
            case 'oldest':
                $query->orderBy('user_bookmarks.created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            default:
                $query->orderBy('user_bookmarks.created_at', 'desc');
                break;
        }

        return $query->paginate($filters['per_page']);
    }

    private function getUserAnalytics(User $user): array
    {
        if (!Gate::allows('view-analytics', $user)) {
            return [];
        }

        return $this->analyticsService->getUserAnalytics($user->id, 'month');
    }

    private function getDefaultNotificationPreferences(): array
    {
        return [
            'email' => [
                'comments' => true,
                'likes' => true,
                'follows' => true,
                'articles' => false,
            ],
            'push' => [
                'comments' => true,
                'likes' => false,
                'follows' => true,
                'articles' => false,
            ],
            'digest' => [
                'weekly' => true,
            ],
            'marketing' => [
                'emails' => false,
            ],
        ];
    }
}