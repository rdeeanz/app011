<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

class NotificationService
{
    // ===== EDITORIAL WORKFLOW NOTIFICATIONS =====

    /**
     * Notify editors when an article is submitted for review
     */
    public function notifyEditorsOfSubmission(Article $article): void
    {
        $editors = User::where('role', 'editor')->get();
        
        foreach ($editors as $editor) {
            $this->sendNotification($editor, [
                'type' => 'article_submitted',
                'title' => 'New Article Submitted for Review',
                'message' => "\"{$article->title}\" has been submitted for review by {$article->author->name}",
                'article_id' => $article->id,
                'action_url' => route('admin.articles.show', $article),
                'priority' => 'medium',
            ]);
        }
        
        Log::info('Editors notified of article submission', [
            'article_id' => $article->id,
            'editors_count' => $editors->count()
        ]);
    }

    /**
     * Notify author when article is approved
     */
    public function notifyAuthorOfApproval(Article $article): void
    {
        $this->sendNotification($article->author, [
            'type' => 'article_approved',
            'title' => 'Article Approved',
            'message' => "Your article \"{$article->title}\" has been approved and is ready for publication",
            'article_id' => $article->id,
            'action_url' => route('admin.articles.show', $article),
            'priority' => 'high',
        ]);
    }

    /**
     * Notify author when article is rejected
     */
    public function notifyAuthorOfRejection(Article $article, string $reason = null): void
    {
        $message = "Your article \"{$article->title}\" has been rejected";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        
        $this->sendNotification($article->author, [
            'type' => 'article_rejected',
            'title' => 'Article Rejected',
            'message' => $message,
            'article_id' => $article->id,
            'action_url' => route('admin.articles.edit', $article),
            'priority' => 'high',
            'metadata' => ['reason' => $reason],
        ]);
    }

    /**
     * Notify publishers when article is scheduled
     */
    public function notifyPublishersOfScheduledArticle(Article $article): void
    {
        $publishers = User::where('role', 'publisher')->get();
        
        foreach ($publishers as $publisher) {
            $this->sendNotification($publisher, [
                'type' => 'article_scheduled',
                'title' => 'Article Scheduled for Publication',
                'message' => "\"{$article->title}\" is scheduled to be published on {$article->published_at->format('M d, Y \a\\t H:i')}",
                'article_id' => $article->id,
                'action_url' => route('admin.articles.show', $article),
                'priority' => 'medium',
            ]);
        }
    }

    // ===== SUBSCRIBER NOTIFICATIONS =====

    /**
     * Notify subscribers of new published articles
     */
    public function notifySubscribersOfNewArticle(Article $article): void
    {
        // Only notify for published articles
        if (!$article->isPublished()) {
            return;
        }
        
        // Get category subscribers
        $categorySubscribers = User::whereHas('subscriptions', function ($query) use ($article) {
            $query->where('category_id', $article->category_id)
                  ->where('is_active', true);
        })->get();
        
        // Get newsletter subscribers
        $newsletterSubscribers = User::where('newsletter_subscribed', true)
            ->where('newsletter_frequency', '!=', 'never')
            ->get();
        
        $allSubscribers = $categorySubscribers->merge($newsletterSubscribers)->unique('id');
        
        foreach ($allSubscribers as $subscriber) {
            // Check user's notification preferences
            if ($this->shouldNotifyUser($subscriber, 'new_article')) {
                Queue::push(function () use ($subscriber, $article) {
                    $this->sendNewArticleNotification($subscriber, $article);
                });
            }
        }
        
        Log::info('Subscribers notified of new article', [
            'article_id' => $article->id,
            'subscribers_count' => $allSubscribers->count()
        ]);
    }

    /**
     * Notify users of breaking news
     */
    public function notifyOfBreakingNews(Article $article): void
    {
        if (!$article->is_breaking) {
            return;
        }
        
        $subscribers = User::where('breaking_news_notifications', true)->get();
        
        foreach ($subscribers as $subscriber) {
            $this->sendBreakingNewsNotification($subscriber, $article);
        }
        
        // Also send push notifications if enabled
        $this->sendBreakingNewsPushNotification($article);
    }

    // ===== COMMENT NOTIFICATIONS =====

    /**
     * Notify article author of new comment
     */
    public function notifyAuthorOfComment(Comment $comment): void
    {
        $article = $comment->article;
        $author = $article->author;
        
        // Don't notify if author commented on their own article
        if ($comment->user_id === $author->id) {
            return;
        }
        
        $this->sendNotification($author, [
            'type' => 'new_comment',
            'title' => 'New Comment on Your Article',
            'message' => "{$comment->author->name} commented on \"{$article->title}\"",
            'article_id' => $article->id,
            'comment_id' => $comment->id,
            'action_url' => $article->url . '#comment-' . $comment->id,
            'priority' => 'low',
        ]);
    }

    /**
     * Notify users of comment replies
     */
    public function notifyOfCommentReply(Comment $reply, Comment $parentComment): void
    {
        $parentAuthor = $parentComment->author;
        
        // Don't notify if replying to own comment
        if ($reply->user_id === $parentAuthor->id) {
            return;
        }
        
        $this->sendNotification($parentAuthor, [
            'type' => 'comment_reply',
            'title' => 'Someone Replied to Your Comment',
            'message' => "{$reply->author->name} replied to your comment on \"{$reply->article->title}\"",
            'article_id' => $reply->article_id,
            'comment_id' => $reply->id,
            'action_url' => $reply->article->url . '#comment-' . $reply->id,
            'priority' => 'medium',
        ]);
    }

    // ===== SYSTEM NOTIFICATIONS =====

    /**
     * Notify administrators of system events
     */
    public function notifyAdminsOfSystemEvent(string $event, array $data = []): void
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            $this->sendNotification($admin, [
                'type' => 'system_event',
                'title' => 'System Event: ' . ucfirst(str_replace('_', ' ', $event)),
                'message' => $this->formatSystemEventMessage($event, $data),
                'priority' => $data['priority'] ?? 'medium',
                'metadata' => $data,
            ]);
        }
    }

    /**
     * Notify of content moderation needs
     */
    public function notifyModeratorsOfContent($content, string $reason): void
    {
        $moderators = User::where('role', 'moderator')->get();
        
        $contentType = class_basename(get_class($content));
        
        foreach ($moderators as $moderator) {
            $this->sendNotification($moderator, [
                'type' => 'moderation_needed',
                'title' => 'Content Moderation Required',
                'message' => "A {$contentType} needs moderation. Reason: {$reason}",
                'content_type' => get_class($content),
                'content_id' => $content->id,
                'reason' => $reason,
                'priority' => 'high',
            ]);
        }
    }

    // ===== NEWSLETTER MANAGEMENT =====

    /**
     * Send newsletter to subscribers
     */
    public function sendNewsletter(array $articles, string $frequency = 'weekly'): void
    {
        $subscribers = User::where('newsletter_subscribed', true)
            ->where('newsletter_frequency', $frequency)
            ->get();
        
        foreach ($subscribers as $subscriber) {
            Queue::push(function () use ($subscriber, $articles, $frequency) {
                $this->sendNewsletterEmail($subscriber, $articles, $frequency);
            });
        }
        
        Log::info('Newsletter sent', [
            'frequency' => $frequency,
            'subscribers_count' => $subscribers->count(),
            'articles_count' => count($articles)
        ]);
    }

    // ===== NOTIFICATION DELIVERY METHODS =====

    /**
     * Send notification through multiple channels
     */
    protected function sendNotification(User $user, array $notificationData): void
    {
        try {
            // Database notification (always create)
            $user->notifications()->create([
                'type' => $notificationData['type'],
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => json_encode($notificationData),
                'priority' => $notificationData['priority'] ?? 'medium',
            ]);
            
            // Email notification (if user prefers email)
            if ($this->shouldSendEmail($user, $notificationData['type'])) {
                $this->sendEmailNotification($user, $notificationData);
            }
            
            // Push notification (if enabled and high priority)
            if ($this->shouldSendPushNotification($user, $notificationData)) {
                $this->sendWebPushNotification($user, $notificationData);
            }
            
            // Real-time notification (WebSocket)
            $this->sendRealTimeNotification($user, $notificationData);
            
        } catch (\Exception $e) {
            Log::error('Notification sending failed', [
                'user_id' => $user->id,
                'type' => $notificationData['type'],
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendEmailNotification(User $user, array $data): void
    {
        try {
            Mail::send('emails.notification', [
                'user' => $user,
                'notification' => $data
            ], function ($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                       ->subject($data['title']);
            });
        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendWebPushNotification(User $user, array $data): void
    {
        // Implementation for web push notifications
        // This would integrate with a service like Pusher, OneSignal, or Firebase
        
        try {
            // Placeholder for push notification implementation
            Log::info('Push notification would be sent', [
                'user_id' => $user->id,
                'title' => $data['title']
            ]);
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendRealTimeNotification(User $user, array $data): void
    {
        // Implementation for real-time notifications via WebSocket
        // This would integrate with Laravel Reverb or Pusher
        
        try {
            // Placeholder for real-time notification
            Log::info('Real-time notification would be sent', [
                'user_id' => $user->id,
                'type' => $data['type']
            ]);
        } catch (\Exception $e) {
            Log::error('Real-time notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== UTILITY METHODS =====

    protected function shouldNotifyUser(User $user, string $notificationType): bool
    {
        // Check user's notification preferences
        $preferences = $user->notification_preferences ?? [];
        
        return $preferences[$notificationType] ?? true;
    }

    protected function shouldSendEmail(User $user, string $notificationType): bool
    {
        $emailPreferences = $user->email_notification_preferences ?? [];
        
        return $emailPreferences[$notificationType] ?? false;
    }

    protected function shouldSendPushNotification(User $user, array $data): bool
    {
        $pushPreferences = $user->push_notification_preferences ?? [];
        $priority = $data['priority'] ?? 'medium';
        
        // Always send high priority push notifications
        if ($priority === 'high') {
            return true;
        }
        
        return $pushPreferences[$data['type']] ?? false;
    }

    protected function formatSystemEventMessage(string $event, array $data): string
    {
        return match($event) {
            'high_traffic' => "Unusual traffic spike detected: {$data['current_users']} active users",
            'storage_low' => "Storage space is running low: {$data['available_space']}GB remaining",
            'database_slow' => "Database performance degraded: {$data['avg_response_time']}ms average response",
            'failed_login_attempts' => "Multiple failed login attempts detected for user: {$data['email']}",
            default => "System event: {$event}"
        };
    }

    protected function sendNewArticleNotification(User $user, Article $article): void
    {
        $this->sendNotification($user, [
            'type' => 'new_article',
            'title' => 'New Article Published',
            'message' => "New article in {$article->category->name}: \"{$article->title}\"",
            'article_id' => $article->id,
            'action_url' => $article->url,
            'priority' => 'low',
        ]);
    }

    protected function sendBreakingNewsNotification(User $user, Article $article): void
    {
        $this->sendNotification($user, [
            'type' => 'breaking_news',
            'title' => '🚨 Breaking News',
            'message' => $article->title,
            'article_id' => $article->id,
            'action_url' => $article->url,
            'priority' => 'high',
        ]);
    }

    protected function sendBreakingNewsPushNotification(Article $article): void
    {
        // Send to all users who have push notifications enabled for breaking news
        $users = User::where('breaking_news_push', true)->get();
        
        foreach ($users as $user) {
            $this->sendWebPushNotification($user, [
                'type' => 'breaking_news',
                'title' => '🚨 Breaking News',
                'message' => $article->title,
                'article_id' => $article->id,
                'action_url' => $article->url,
                'priority' => 'high',
            ]);
        }
    }

    protected function sendNewsletterEmail(User $user, array $articles, string $frequency): void
    {
        try {
            $templateName = $this->getEmailTemplate('newsletter', $frequency);
            $subject = $this->getEmailSubject('newsletter', $frequency);

            Mail::send($templateName, [
                'user' => $user,
                'articles' => $articles,
                'frequency' => $frequency,
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
            ], function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                       ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Newsletter email failed', [
                'user_id' => $user->id,
                'frequency' => $frequency,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== EMAIL TEMPLATE METHODS =====

    /**
     * Get email template with fallback handling
     */
    private function getEmailTemplate(string $type, string $variant = 'default'): string
    {
        $templates = [
            'newsletter' => [
                'daily' => 'emails.newsletter.daily',
                'weekly' => 'emails.newsletter.weekly',
                'monthly' => 'emails.newsletter.monthly',
                'default' => 'emails.newsletter.default',
            ],
            'article_notification' => [
                'approved' => 'emails.article.approved',
                'rejected' => 'emails.article.rejected',
                'published' => 'emails.article.published',
                'default' => 'emails.article.default',
            ],
            'comment_notification' => [
                'new' => 'emails.comment.new',
                'reply' => 'emails.comment.reply',
                'default' => 'emails.comment.default',
            ]
        ];

        $templatePath = $templates[$type][$variant] ?? $templates[$type]['default'] ?? 'emails.default';

        // Check if template exists, fallback to basic template if not
        if (!view()->exists($templatePath)) {
            Log::warning("Email template not found: {$templatePath}, using fallback");
            return 'emails.default';
        }

        return $templatePath;
    }

    /**
     * Get email subject with fallback
     */
    private function getEmailSubject(string $type, string $variant = 'default'): string
    {
        $subjects = [
            'newsletter' => [
                'daily' => 'Daily Newsletter - ' . config('app.name'),
                'weekly' => 'Weekly Newsletter - ' . config('app.name'),
                'monthly' => 'Monthly Newsletter - ' . config('app.name'),
                'default' => 'Newsletter - ' . config('app.name'),
            ],
            'article_notification' => [
                'approved' => 'Your Article Has Been Approved',
                'rejected' => 'Article Review Update',
                'published' => 'Your Article Is Now Live',
                'default' => 'Article Update - ' . config('app.name'),
            ],
            'comment_notification' => [
                'new' => 'New Comment on Your Article',
                'reply' => 'Someone Replied to Your Comment',
                'default' => 'New Activity - ' . config('app.name'),
            ]
        ];

        return $subjects[$type][$variant] ?? $subjects[$type]['default'] ?? config('app.name') . ' Notification';
    }

    /**
     * Validate email template data
     */
    private function validateEmailData(array $data, string $templateType): array
    {
        $requiredFields = [
            'newsletter' => ['user', 'articles'],
            'article_notification' => ['user', 'article'],
            'comment_notification' => ['user', 'comment', 'article'],
        ];

        $required = $requiredFields[$templateType] ?? ['user'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field for email template: {$field}");
            }
        }

        // Add default values
        $data['app_name'] = $data['app_name'] ?? config('app.name');
        $data['app_url'] = $data['app_url'] ?? config('app.url');
        $data['support_email'] = $data['support_email'] ?? config('mail.from.address');

        return $data;
    }

    // ===== BULK OPERATIONS =====

    /**
     * Send bulk notification to multiple users
     */
    public function sendBulkNotification(array $userIds, array $notificationData): void
    {
        $users = User::whereIn('id', $userIds)->get();
        
        foreach ($users as $user) {
            Queue::push(function () use ($user, $notificationData) {
                $this->sendNotification($user, $notificationData);
            });
        }
        
        Log::info('Bulk notification queued', [
            'users_count' => $users->count(),
            'type' => $notificationData['type']
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(User $user, array $notificationIds): bool
    {
        try {
            return $user->notifications()
                       ->whereIn('id', $notificationIds)
                       ->update(['read_at' => now()]) > 0;
        } catch (\Exception $e) {
            Log::error('Mark notifications as read failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        return DB::table('notifications')
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}