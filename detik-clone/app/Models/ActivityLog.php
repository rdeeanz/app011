<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'action',
        'description',
        'properties',
        'batch_uuid',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    // Disable updated_at as we only need created_at for logs
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    // ===== RELATIONSHIPS =====

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    // ===== SCOPES =====

    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeCausedBy($query, Model $causer)
    {
        return $query->where('causer_type', get_class($causer))
                    ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
                    ->where('subject_id', $subject->getKey());
    }

    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ===== STATIC METHODS =====

    public static function log(
        string $action,
        Model $subject = null,
        Model $causer = null,
        string $description = null,
        array $properties = []
    ): self {
        return static::create([
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'causer_type' => $causer ? get_class($causer) : (auth()->check() ? get_class(auth()->user()) : null),
            'causer_id' => $causer?->getKey() ?? auth()->id(),
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    public static function logArticleAction(
        Article $article,
        string $action,
        User $user = null,
        array $properties = []
    ): self {
        $descriptions = [
            'created' => 'Article was created',
            'updated' => 'Article was updated',
            'published' => 'Article was published',
            'unpublished' => 'Article was unpublished',
            'scheduled' => 'Article was scheduled for publication',
            'featured' => 'Article was featured',
            'unfeatured' => 'Article was unfeatured',
            'archived' => 'Article was archived',
            'deleted' => 'Article was deleted',
            'submitted_for_review' => 'Article was submitted for review',
            'approved' => 'Article was approved',
            'rejected' => 'Article was rejected',
        ];

        return static::log(
            $action,
            $article,
            $user ?? auth()->user(),
            $descriptions[$action] ?? "Article {$action}",
            array_merge([
                'article_title' => $article->title,
                'article_slug' => $article->slug,
                'article_status' => $article->status,
                'editorial_status' => $article->editorial_status,
            ], $properties)
        );
    }

    public static function getRecentActivity(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['subject', 'causer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getUserActivity(User $user, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->causedBy($user)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getArticleHistory(Article $article): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->forSubject($article)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ===== ACCESSORS =====

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getCauserNameAttribute(): ?string
    {
        if (!$this->causer) {
            return 'System';
        }
        
        // Check if causer has a name property/method
        if (method_exists($this->causer, 'getDisplayName')) {
            return $this->causer->getDisplayName();
        }
        
        if (property_exists($this->causer, 'name') || isset($this->causer->name)) {
            return $this->causer->name;
        }
        
        return 'Unknown User';
    }

    public function getSubjectTitleAttribute(): ?string
    {
        if (!$this->subject) {
            return 'Unknown';
        }
        
        // Check for display name method first
        if (method_exists($this->subject, 'getDisplayName')) {
            return $this->subject->getDisplayName();
        }
        
        // Check for specific model types and their known properties
        if ($this->subject instanceof Article) {
            return $this->subject->title ?? 'Untitled Article';
        }
        
        // Check for common properties safely
        if (property_exists($this->subject, 'title') || isset($this->subject->title)) {
            return $this->subject->title;
        }
        
        if (property_exists($this->subject, 'name') || isset($this->subject->name)) {
            return $this->subject->name;
        }
        
        return 'Unknown';
    }

    // ===== METHODS =====

    public function getProperty(string $key, mixed $default = null): mixed
    {
        return data_get($this->properties, $key, $default);
    }

    public function hasProperty(string $key): bool
    {
        return array_key_exists($key, $this->properties ?? []);
    }

    public function getChanges(): array
    {
        return $this->getProperty('changes', []);
    }

    public function getOldValues(): array
    {
        return $this->getProperty('old', []);
    }

    public function getNewValues(): array
    {
        return $this->getProperty('attributes', []);
    }
}