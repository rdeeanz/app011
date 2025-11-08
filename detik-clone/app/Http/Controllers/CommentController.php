<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Article;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

/**
 * CommentController - Comment Management Controller
 * 
 * Features:
 * - Comment CRUD operations with threading support
 * - Comment moderation and approval workflow
 * - Spam detection and reporting system
 * - Like/dislike functionality
 * - Rate limiting for comment posting
 * - Guest and authenticated user comments
 */
class CommentController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {
        $this->middleware('throttle:comments')->only(['store']);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request, Article $article): JsonResponse|RedirectResponse
    {
        // Rate limiting
        $key = 'comment-attempt:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->jsonOrRedirect($request, [
                'error' => "Too many comments. Please wait $seconds seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 300); // 5 minutes

        // Validation
        $rules = [
            'content' => 'required|string|min:10|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ];

        if (!Auth::check()) {
            $rules['author_name'] = 'required|string|max:100';
            $rules['author_email'] = 'required|email|max:255';
        }

        $validated = $request->validate($rules);

        // Check if article allows comments
        if (!$article->allow_comments) {
            return $this->jsonOrRedirect($request, [
                'error' => 'Comments are disabled for this article.',
            ], 403);
        }

        // Check if parent comment exists and belongs to this article
        if ($validated['parent_id']) {
            $parentComment = Comment::find($validated['parent_id']);
            if (!$parentComment || $parentComment->article_id !== $article->id) {
                return $this->jsonOrRedirect($request, [
                    'error' => 'Invalid parent comment.',
                ], 400);
            }
        }

        try {
            $commentData = [
                'article_id' => $article->id,
                'content' => $validated['content'],
                'parent_id' => $validated['parent_id'] ?? null,
            ];
            
            // Only collect PII with explicit consent
            if (config('app.collect_comment_analytics', false) && $request->input('consent_analytics', false)) {
                // Anonymize IP address (keep first 3 octets for IPv4, first 64 bits for IPv6)
                $ip = $request->ip();
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $commentData['ip_address'] = preg_replace('/\.\d+$/', '.0', $ip);
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $commentData['ip_address'] = substr($ip, 0, strpos($ip, ':', strpos($ip, ':', strpos($ip, ':', strpos($ip, ':') + 1) + 1) + 1)) . '::';
                }
                
                // Store hashed user agent instead of raw
                if ($request->userAgent()) {
                    $commentData['user_agent'] = hash('sha256', $request->userAgent() . config('app.key'));
                }
                
                // Set retention timestamp
                $commentData['analytics_expires_at'] = now()->addMonths(6);
            }

            if (Auth::check()) {
                $commentData['user_id'] = Auth::id();
                $commentData['status'] = $this->shouldAutoApprove(Auth::user()) ? 'approved' : 'pending';
            } else {
                $commentData['author_name'] = $validated['author_name'];
                $commentData['author_email'] = $validated['author_email'];
                $commentData['status'] = 'pending'; // Guest comments always need approval
            }

            $comment = Comment::create($commentData);

            // Track comment creation
            $this->analyticsService->trackEvent('comment_created', [
                'article_id' => $article->id,
                'comment_id' => $comment->id,
                'user_id' => Auth::id(),
                'is_reply' => !is_null($validated['parent_id']),
                'content_length' => strlen($validated['content']),
            ]);

            $message = $comment->status === 'approved' 
                ? 'Comment posted successfully!' 
                : 'Comment submitted for review.';

            return $this->jsonOrRedirect($request, [
                'success' => true,
                'message' => $message,
                'comment' => $comment->load('user'),
                'status' => $comment->status,
            ]);

        } catch (\Exception $e) {
            return $this->jsonOrRedirect($request, [
                'error' => 'Failed to post comment. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => 'required|string|min:10|max:2000',
        ]);

        try {
            $comment->update([
                'content' => $validated['content'],
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully!',
                'comment' => $comment->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update comment.',
            ], 500);
        }
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        try {
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete comment.',
            ], 500);
        }
    }

    /**
     * Like a comment
     */
    public function like(Comment $comment): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $user = Auth::user();
        $cacheKey = "comment_like_{$comment->id}_{$user->id}";

        // Check if user already liked this comment (using simple cache for demo)
        if (cache()->has($cacheKey)) {
            return response()->json([
                'error' => 'You have already liked this comment.',
            ], 400);
        }

        try {
            $comment->like();
            
            // Cache the like to prevent duplicate likes
            cache()->put($cacheKey, true, 86400); // 24 hours

            // Track like event
            $this->analyticsService->trackEvent('comment_liked', [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'article_id' => $comment->article_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment liked!',
                'likes_count' => $comment->fresh()->likes_count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to like comment.',
            ], 500);
        }
    }

    /**
     * Dislike a comment
     */
    public function dislike(Comment $comment): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $user = Auth::user();
        $cacheKey = "comment_dislike_{$comment->id}_{$user->id}";

        if (cache()->has($cacheKey)) {
            return response()->json([
                'error' => 'You have already disliked this comment.',
            ], 400);
        }

        try {
            $comment->dislike();
            
            // Cache the dislike
            cache()->put($cacheKey, true, 86400);

            // Track dislike event
            $this->analyticsService->trackEvent('comment_disliked', [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'article_id' => $comment->article_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment disliked!',
                'dislikes_count' => $comment->fresh()->dislikes_count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to dislike comment.',
            ], 500);
        }
    }

    /**
     * Report a comment
     */
    public function report(Request $request, Comment $comment): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->input('reason', 'Inappropriate content');
        $userKey = Auth::check() ? 'user_' . Auth::id() : 'ip_' . request()->ip();
        $cacheKey = "comment_report_{$comment->id}_{$userKey}";

        // Prevent duplicate reports
        if (cache()->has($cacheKey)) {
            return response()->json([
                'error' => 'You have already reported this comment.',
            ], 400);
        }

        try {
            $comment->report($reason);
            
            // Cache the report
            cache()->put($cacheKey, true, 86400);

            // Track report event
            $this->analyticsService->trackEvent('comment_reported', [
                'comment_id' => $comment->id,
                'user_id' => Auth::id(),
                'article_id' => $comment->article_id,
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment reported. Thank you for helping keep our community safe.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to report comment.',
            ], 500);
        }
    }

    /**
     * Approve a comment (moderator only)
     */
    public function approve(Comment $comment): JsonResponse
    {
        Gate::authorize('moderate-comments');

        try {
            $comment->approve(Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Comment approved successfully!',
                'comment' => $comment->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to approve comment.',
            ], 500);
        }
    }

    /**
     * Reject a comment (moderator only)
     */
    public function reject(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('moderate-comments');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $comment->reject(Auth::user(), $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Comment rejected.',
                'comment' => $comment->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reject comment.',
            ], 500);
        }
    }

    /**
     * Pin/unpin a comment (moderator only)
     */
    public function togglePin(Comment $comment): JsonResponse
    {
        Gate::authorize('moderate-comments');

        try {
            $comment->togglePin();

            return response()->json([
                'success' => true,
                'message' => $comment->fresh()->is_pinned ? 'Comment pinned!' : 'Comment unpinned!',
                'is_pinned' => $comment->fresh()->is_pinned,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to toggle pin status.',
            ], 500);
        }
    }

    /**
     * Load more comments (for pagination)
     */
    public function loadMore(Request $request, Article $article): JsonResponse
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $page = $request->input('page');
        $parentId = $request->input('parent_id');

        $query = $article->comments()->approved();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $comments = $query->with(['user', 'approvedReplies.user'])
            ->latest()
            ->paginate(10, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'comments' => $comments->items(),
            'hasMore' => $comments->hasMorePages(),
            'nextPage' => $comments->currentPage() + 1,
            'total' => $comments->total(),
        ]);
    }

    // ===== PRIVATE HELPER METHODS =====

    private function shouldAutoApprove($user): bool
    {
        // Auto-approve for trusted users, admins, or users with good history
        if ($user->hasRole(['admin', 'moderator', 'editor'])) {
            return true;
        }

        // Auto-approve for users with approved comments
        $approvedComments = Comment::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        return $approvedComments >= 3;
    }

    private function jsonOrRedirect(Request $request, array $data, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json($data, $status);
        }

        if (isset($data['error'])) {
            return back()->withErrors(['comment' => $data['error']]);
        }

        return back()->with('success', $data['message'] ?? 'Operation completed successfully.');
    }
}