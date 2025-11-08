<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Comment Model - Article Commenting System
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_id',
        'user_id', 
        'parent_id',
        'content',
        'author_name',
        'author_email',
        'status',
        'approved_at',
        'approved_by',
        'likes_count',
        'dislikes_count',
        'replies_count',
        'is_pinned',
        'reports_count',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'likes_count' => 'integer',
        'dislikes_count' => 'integer', 
        'replies_count' => 'integer',
        'is_pinned' => 'boolean',
        'reports_count' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'likes_count' => 0,
        'dislikes_count' => 0,
        'replies_count' => 0,
        'is_pinned' => false,
        'reports_count' => 0,
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SPAM = 'spam';

    // ===== RELATIONSHIPS =====

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function approvedReplies(): HasMany
    {
        return $this->replies()->where('status', self::STATUS_APPROVED)->orderBy('created_at');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ===== QUERY SCOPES =====

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeParent(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    // ===== HELPER METHODS =====

    public function approve(?User $moderator = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $moderator?->id,
        ]);
    }

    public function reject(?User $moderator = null, ?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $moderator?->id,
            'rejection_reason' => $reason,
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasReplies(): bool
    {
        return $this->replies()->where('status', self::STATUS_APPROVED)->exists();
    }

    public function like(): bool
    {
        return $this->increment('likes_count');
    }

    public function dislike(): bool
    {
        return $this->increment('dislikes_count');
    }

    public function report(?string $reason = null): bool
    {
        // Check if user has already reported this comment
        if (CommentReport::where('comment_id', $this->id)
                         ->where('user_id', auth()->id())
                         ->exists()) {
            return false; // User has already reported this comment
        }
        
        return DB::transaction(function () use ($reason) {
            // Create CommentReport record
            CommentReport::create([
                'comment_id' => $this->id,
                'user_id' => auth()->id(),
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]);
            
            // Increment reports count
            return $this->increment('reports_count');
        });
    }

    public function togglePin(): bool
    {
        $this->is_pinned = !$this->is_pinned;
        return $this->save();
    }
}